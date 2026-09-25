/**
 * Minimal IndexedDB wrapper for offline POS (§28/§32). Two stores:
 *  - medicines: cached catalog snapshot (id, name, barcode, price, stock...)
 *  - sync_queue: offline sales waiting to be pushed to the server
 * The server is always the source of truth for stock — this cache only
 * lets the cashier keep working while offline; every queued sale is
 * re-validated server-side on sync (see SyncService::processSale).
 */
window.ApotekOfflineDB = (function () {
  'use strict';

  var DB_NAME = 'apotekcare_offline';
  var DB_VERSION = 1;
  var dbPromise = null;

  function open() {
    if (dbPromise) return dbPromise;
    dbPromise = new Promise(function (resolve, reject) {
      var req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onupgradeneeded = function (e) {
        var db = e.target.result;
        if (!db.objectStoreNames.contains('medicines')) {
          db.createObjectStore('medicines', { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains('sync_queue')) {
          db.createObjectStore('sync_queue', { keyPath: 'uuid' });
        }
      };
      req.onsuccess = function (e) { resolve(e.target.result); };
      req.onerror = function () { reject(req.error); };
    });
    return dbPromise;
  }

  function tx(storeName, mode) {
    return open().then(function (db) { return db.transaction(storeName, mode).objectStore(storeName); });
  }

  return {
    cacheMedicines: function (list) {
      return tx('medicines', 'readwrite').then(function (store) {
        return new Promise(function (resolve, reject) {
          store.clear();
          list.forEach(function (m) { store.put(m); });
          store.transaction.oncomplete = resolve;
          store.transaction.onerror = function () { reject(store.transaction.error); };
        });
      });
    },

    searchMedicines: function (query, categoryUnusedForOffline) {
      var q = (query || '').toLowerCase();
      return tx('medicines', 'readonly').then(function (store) {
        return new Promise(function (resolve, reject) {
          var results = [];
          var req = store.openCursor();
          req.onsuccess = function (e) {
            var cursor = e.target.result;
            if (!cursor) { resolve(results.slice(0, 40)); return; }
            var m = cursor.value;
            if (!q || (m.name && m.name.toLowerCase().indexOf(q) !== -1) ||
                (m.code && m.code.toLowerCase().indexOf(q) !== -1) ||
                (m.barcode && String(m.barcode).indexOf(q) !== -1)) {
              results.push(m);
            }
            cursor.continue();
          };
          req.onerror = function () { reject(req.error); };
        });
      });
    },

    getByBarcode: function (code) {
      return tx('medicines', 'readonly').then(function (store) {
        return new Promise(function (resolve, reject) {
          var req = store.openCursor();
          req.onsuccess = function (e) {
            var cursor = e.target.result;
            if (!cursor) { resolve(null); return; }
            if (cursor.value.barcode === code) { resolve(cursor.value); return; }
            cursor.continue();
          };
          req.onerror = function () { reject(req.error); };
        });
      });
    },

    adjustCachedStock: function (medicineId, deltaQty) {
      return tx('medicines', 'readwrite').then(function (store) {
        return new Promise(function (resolve) {
          var req = store.get(medicineId);
          req.onsuccess = function () {
            var m = req.result;
            if (m) {
              m.stock = Math.max(0, (parseFloat(m.stock) || 0) + deltaQty);
              store.put(m);
            }
            resolve();
          };
          req.onerror = function () { resolve(); };
        });
      });
    },

    queueSale: function (entry) {
      return tx('sync_queue', 'readwrite').then(function (store) {
        return new Promise(function (resolve, reject) {
          var req = store.put(entry);
          req.onsuccess = function () { resolve(entry); };
          req.onerror = function () { reject(req.error); };
        });
      });
    },

    getPendingSales: function () {
      return tx('sync_queue', 'readonly').then(function (store) {
        return new Promise(function (resolve, reject) {
          var req = store.getAll();
          req.onsuccess = function () { resolve(req.result.filter(function (r) { return r.status === 'pending'; })); };
          req.onerror = function () { reject(req.error); };
        });
      });
    },

    countPending: function () {
      return this.getPendingSales().then(function (rows) { return rows.length; });
    },

    markSynced: function (uuid) {
      return tx('sync_queue', 'readwrite').then(function (store) {
        return new Promise(function (resolve) {
          store.delete(uuid);
          store.transaction.oncomplete = resolve;
        });
      });
    },

    markFailed: function (uuid, message) {
      return tx('sync_queue', 'readwrite').then(function (store) {
        return new Promise(function (resolve) {
          var req = store.get(uuid);
          req.onsuccess = function () {
            var entry = req.result;
            if (entry) {
              entry.status = 'pending'; // keep retrying automatically; server tracks real conflicts
              entry.last_error = message;
              store.put(entry);
            }
            resolve();
          };
        });
      });
    },
  };
})();
