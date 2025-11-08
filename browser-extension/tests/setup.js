// Jest setup file

// Add TextEncoder/TextDecoder polyfill for Node.js
const { TextEncoder, TextDecoder } = require('util');
global.TextEncoder = TextEncoder;
global.TextDecoder = TextDecoder;

// Mock Web Crypto API
global.crypto = {
  subtle: {
    generateKey: jest.fn(() => Promise.resolve({ type: 'secret' })),
    encrypt: jest.fn(() => Promise.resolve(new Uint8Array([1, 2, 3]))),
    decrypt: jest.fn(() => Promise.resolve(new Uint8Array([72, 101, 108, 108, 111]))), // "Hello"
    exportKey: jest.fn(() => Promise.resolve({ kty: 'oct', k: 'test' })),
    importKey: jest.fn(() => Promise.resolve({ type: 'secret' }))
  },
  getRandomValues: jest.fn((arr) => {
    for (let i = 0; i < arr.length; i++) {
      arr[i] = Math.floor(Math.random() * 256);
    }
    return arr;
  })
};

// Mock btoa/atob for base64 encoding/decoding
global.btoa = (str) => Buffer.from(str, 'binary').toString('base64');
global.atob = (b64) => Buffer.from(b64, 'base64').toString('binary');

// Mock browser API for testing
global.browser = {
  storage: {
    local: {
      get: jest.fn(),
      set: jest.fn(),
      remove: jest.fn()
    }
  },
  tabs: {
    query: jest.fn()
  },
  runtime: {
    openOptionsPage: jest.fn(),
    getURL: jest.fn()
  },
  identity: {
    launchWebAuthFlow: jest.fn()
  }
};

// Mock chrome API (alias to browser)
global.chrome = global.browser;
