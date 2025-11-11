import { ref, watch } from 'vue'

/**
 * Vue composable for localStorage persistence
 * @param {string} key - LocalStorage key
 * @param {*} defaultValue - Default value if key doesn't exist
 * @returns {Ref} Reactive reference that syncs with localStorage
 */
export function useLocalStorage(key, defaultValue) {
  // Load initial value from localStorage
  const storedValue = loadFromStorage(key, defaultValue)
  const value = ref(storedValue)

  // Watch for changes and save to localStorage
  watch(
    value,
    newValue => {
      try {
        localStorage.setItem(key, JSON.stringify(newValue))
      } catch (error) {
        console.warn(`Failed to save to localStorage (key: ${key}):`, error)
      }
    },
    { deep: true }
  )

  return value
}

/**
 * Load value from localStorage with error handling
 * @param {string} key - LocalStorage key
 * @param {*} defaultValue - Default value if loading fails
 * @returns {*} Loaded value or default value
 */
function loadFromStorage(key, defaultValue) {
  try {
    const item = localStorage.getItem(key)
    if (item === null) {
      return defaultValue
    }
    return JSON.parse(item)
  } catch (error) {
    console.warn(`Failed to load from localStorage (key: ${key}):`, error)
    return defaultValue
  }
}
