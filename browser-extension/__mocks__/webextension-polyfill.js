// Mock webextension-polyfill for Jest tests
// This mock is automatically used by Jest when webextension-polyfill is imported

// Shared storage data across all tests (can be reset in beforeEach)
export let mockStorageData = {};

// Reset function for tests
export function resetMockStorage() {
  mockStorageData = {};
}

// Mock browser API
const browser = {
  storage: {
    local: {
      get: jest.fn((key) => {
        if (typeof key === 'string') {
          return Promise.resolve({ [key]: mockStorageData[key] });
        }
        // Handle array of keys or null
        if (key === null || key === undefined) {
          return Promise.resolve(mockStorageData);
        }
        const result = {};
        key.forEach(k => {
          if (mockStorageData[k] !== undefined) {
            result[k] = mockStorageData[k];
          }
        });
        return Promise.resolve(result);
      }),
      set: jest.fn((items) => {
        Object.assign(mockStorageData, items);
        return Promise.resolve();
      }),
      remove: jest.fn((keys) => {
        const keysArray = Array.isArray(keys) ? keys : [keys];
        keysArray.forEach(key => {
          delete mockStorageData[key];
        });
        return Promise.resolve();
      }),
      clear: jest.fn(() => {
        mockStorageData = {};
        return Promise.resolve();
      })
    }
  },
  tabs: {
    query: jest.fn(() => Promise.resolve([])),
    create: jest.fn(() => Promise.resolve({ id: 1 })),
    update: jest.fn(() => Promise.resolve()),
    get: jest.fn(() => Promise.resolve({ id: 1, url: 'https://example.com' }))
  },
  runtime: {
    openOptionsPage: jest.fn(() => Promise.resolve()),
    getURL: jest.fn((path) => `chrome-extension://mock-extension-id/${path}`),
    sendMessage: jest.fn(() => Promise.resolve()),
    onMessage: {
      addListener: jest.fn()
    }
  },
  identity: {
    launchWebAuthFlow: jest.fn(() => Promise.resolve('https://example.com/callback?code=test_code'))
  }
};

// Export as default (for import browser from 'webextension-polyfill')
export default browser;
