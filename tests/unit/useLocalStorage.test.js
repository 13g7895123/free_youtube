import { describe, it, expect, beforeEach, vi } from 'vitest'
import { useLocalStorage } from '@/composables/useLocalStorage'

describe('useLocalStorage', () => {
  beforeEach(() => {
    // 清空 localStorage
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('應該使用預設值初始化', () => {
    const stored = useLocalStorage('test-key', { value: 42 })
    expect(stored.value).toEqual({ value: 42 })
  })

  it('應該從 localStorage 載入現有值', () => {
    localStorage.setItem('test-key', JSON.stringify({ value: 100 }))
    const stored = useLocalStorage('test-key', { value: 42 })
    expect(stored.value).toEqual({ value: 100 })
  })

  it('應該在值改變時保存到 localStorage', async () => {
    const stored = useLocalStorage('test-key', { count: 0 })

    stored.value = { count: 5 }

    // 等待 Vue 的 watch 觸發
    await new Promise(resolve => setTimeout(resolve, 0))

    const savedValue = JSON.parse(localStorage.getItem('test-key'))
    expect(savedValue).toEqual({ count: 5 })
  })

  it('應該處理無效的 localStorage 資料', () => {
    localStorage.setItem('test-key', 'invalid json')
    const stored = useLocalStorage('test-key', { value: 'default' })
    expect(stored.value).toEqual({ value: 'default' })
  })

  it('應該處理不同的資料類型', () => {
    const stringValue = useLocalStorage('string-key', 'hello')
    expect(stringValue.value).toBe('hello')

    const numberValue = useLocalStorage('number-key', 42)
    expect(numberValue.value).toBe(42)

    const booleanValue = useLocalStorage('boolean-key', true)
    expect(booleanValue.value).toBe(true)

    const arrayValue = useLocalStorage('array-key', [1, 2, 3])
    expect(arrayValue.value).toEqual([1, 2, 3])
  })
})
