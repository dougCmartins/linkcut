/**
 * Key/value store for image blobs in IndexedDB.
 * Falls back to memory when IndexedDB is missing or blocked, so the app keeps working for the session.
 */
const DATABASE_NAME = 'linkcut'
const STORE_NAME = 'images'

const memoryStore = new Map<string, Blob>()
let databasePromise: Promise<IDBDatabase | null> | null = null

function openDatabase(): Promise<IDBDatabase | null> {
  if (databasePromise) return databasePromise
  databasePromise = new Promise((resolve) => {
    if (typeof indexedDB === 'undefined') return resolve(null)
    try {
      const request = indexedDB.open(DATABASE_NAME, 1)
      request.onupgradeneeded = () => request.result.createObjectStore(STORE_NAME)
      request.onsuccess = () => resolve(request.result)
      request.onerror = () => resolve(null)
    } catch {
      resolve(null)
    }
  })
  return databasePromise
}

function runRequest<T>(database: IDBDatabase, mode: IDBTransactionMode, run: (store: IDBObjectStore) => IDBRequest<T>) {
  return new Promise<T>((resolve, reject) => {
    const request = run(database.transaction(STORE_NAME, mode).objectStore(STORE_NAME))
    request.onsuccess = () => resolve(request.result)
    request.onerror = () => reject(request.error)
  })
}

export async function putImage(id: string, blob: Blob): Promise<void> {
  const database = await openDatabase()
  if (!database) {
    memoryStore.set(id, blob)
    return
  }
  try {
    await runRequest(database, 'readwrite', (store) => store.put(blob, id))
  } catch {
    memoryStore.set(id, blob)
  }
}

export async function getAllImages(): Promise<Map<string, Blob>> {
  const images = new Map(memoryStore)
  const database = await openDatabase()
  if (!database) return images
  try {
    const keys = await runRequest(database, 'readonly', (store) => store.getAllKeys())
    const blobs = await runRequest(database, 'readonly', (store) => store.getAll())
    keys.forEach((key, index) => {
      const blob = blobs[index]
      if (blob instanceof Blob) images.set(String(key), blob)
    })
  } catch {
    // Keep whatever is in memory.
  }
  return images
}

export async function deleteImage(id: string): Promise<void> {
  memoryStore.delete(id)
  const database = await openDatabase()
  if (!database) return
  try {
    await runRequest(database, 'readwrite', (store) => store.delete(id))
  } catch {
    // Nothing else to clean up.
  }
}
