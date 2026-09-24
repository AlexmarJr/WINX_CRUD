import { defineStore } from 'pinia'
import { apiErrorMessage, apiGet, apiWrite } from '~/utils/api'

export interface InventoryCategory {
  id: string
  tenancy_id: string
  user_id: string
  name: string
  description: string | null
  status: 'active' | 'inactive'
  products_count?: number
  created_at: string
  updated_at: string
}

export interface InventoryProduct {
  id: string
  tenancy_id: string
  user_id: string
  category_id: string
  category: InventoryCategory | null
  name: string
  description: string | null
  cost: string | null
  price: string
  stock: number
  status: 'active' | 'inactive'
  image: string | null
  created_at: string
  updated_at: string
}

export interface LowStockProduct {
  id: string
  name: string
  category: string | null
  stock: number
}

export interface InventorySummary {
  product_count: number
  total_units: number
  purchase_total: string
  resale_total: string
  low_stock: LowStockProduct[]
}

interface ResourceResponse<T> {
  data: T
}

interface PageResponse<T> {
  data: T[]
  meta: { current_page: number, last_page: number, total: number }
}

interface PaginationState {
  currentPage: number
  lastPage: number
  total: number
}

const emptyPagination = (): PaginationState => ({ currentPage: 1, lastPage: 1, total: 0 })
let categoriesRequest = 0
let categoriesAbortController: AbortController | undefined
let categoryOptionsRequest = 0
let activeCategoryOptionsRequest = 0
let productsRequest = 0
let productsAbortController: AbortController | undefined
let summaryRequest = 0

export const useInventoryStore = defineStore('inventory', {
  state: () => ({
    categories: [] as InventoryCategory[],
    categoryOptions: [] as InventoryCategory[],
    activeCategoryOptions: [] as InventoryCategory[],
    products: [] as InventoryProduct[],
    summary: null as InventorySummary | null,
    categoriesPagination: emptyPagination(),
    productsPagination: emptyPagination(),
    categoriesLoading: false,
    productsLoading: false,
    summaryLoading: false,
    categoriesError: '',
    productsError: '',
    summaryError: ''
  }),
  actions: {
    async getProductMaxPrice(): Promise<string> {
      const response = await apiGet<ResourceResponse<{ max_price: string }>>('/api/v1/products/max-price')
      return response.data.max_price
    },

    resetInventory(): void {
      this.cancelCategoriesRequest()
      categoryOptionsRequest += 1
      activeCategoryOptionsRequest += 1
      this.cancelProductsRequest()
      summaryRequest += 1
      this.$reset()
    },

    cancelCategoriesRequest(): void {
      categoriesRequest += 1
      categoriesAbortController?.abort()
      categoriesAbortController = undefined
      this.categoriesLoading = false
    },

    cancelProductsRequest(): void {
      productsRequest += 1
      productsAbortController?.abort()
      productsAbortController = undefined
      this.productsLoading = false
    },

    async loadCategories(page = 1, search = '', sortBy = '', sortDir: 'asc' | 'desc' = 'asc'): Promise<void> {
      this.cancelCategoriesRequest()
      const request = categoriesRequest
      const controller = new AbortController()
      categoriesAbortController = controller
      this.categoriesLoading = true
      this.categoriesError = ''

      try {
        const response = await apiGet<PageResponse<InventoryCategory>>('/api/v1/categories', {
          page,
          per_page: 20,
          ...(search ? { search } : {}),
          ...(sortBy ? { sort_by: sortBy, sort_dir: sortDir } : {})
        }, controller.signal)
        if (request !== categoriesRequest) return
        this.categories = response.data
        this.categoriesPagination = {
          currentPage: response.meta.current_page,
          lastPage: response.meta.last_page,
          total: response.meta.total
        }
      } catch (error) {
        if (request !== categoriesRequest || controller.signal.aborted) return
        this.categories = []
        this.categoriesError = apiErrorMessage(error, 'Não foi possível carregar as categorias.')
      } finally {
        if (request === categoriesRequest) {
          categoriesAbortController = undefined
          this.categoriesLoading = false
        }
      }
    },

    async loadCategoryOptions(): Promise<void> {
      const request = ++categoryOptionsRequest
      const categories: InventoryCategory[] = []
      let page = 1
      let lastPage = 1

      do {
        const response = await apiGet<PageResponse<InventoryCategory>>('/api/v1/categories', { page, per_page: 100 })
        if (request !== categoryOptionsRequest) return
        categories.push(...response.data)
        lastPage = response.meta.last_page
        page += 1
      } while (page <= lastPage)

      if (request === categoryOptionsRequest) this.categoryOptions = categories
    },

    async loadActiveCategoryOptions(): Promise<void> {
      const request = ++activeCategoryOptionsRequest
      const categories: InventoryCategory[] = []
      let page = 1
      let lastPage = 1

      do {
        const response = await apiGet<PageResponse<InventoryCategory>>('/api/v1/categories', { page, per_page: 100, status: 'active' })
        if (request !== activeCategoryOptionsRequest) return
        categories.push(...response.data)
        lastPage = response.meta.last_page
        page += 1
      } while (page <= lastPage)

      if (request === activeCategoryOptionsRequest) this.activeCategoryOptions = categories
    },

    async loadProducts(page = 1, search = '', categoryId = '', minPrice = '', maxPrice = '', sortBy = '', sortDir: 'asc' | 'desc' = 'asc'): Promise<void> {
      this.cancelProductsRequest()
      const request = productsRequest
      const controller = new AbortController()
      productsAbortController = controller
      this.productsLoading = true
      this.productsError = ''

      try {
        const response = await apiGet<PageResponse<InventoryProduct>>('/api/v1/products', {
          page,
          per_page: 20,
          ...(search ? { search } : {}),
          ...(categoryId ? { category_id: categoryId } : {}),
          ...(minPrice ? { min_price: minPrice } : {}),
          ...(maxPrice ? { max_price: maxPrice } : {}),
          ...(sortBy ? { sort_by: sortBy, sort_dir: sortDir } : {})
        }, controller.signal)
        if (request !== productsRequest) return
        this.products = response.data
        this.productsPagination = {
          currentPage: response.meta.current_page,
          lastPage: response.meta.last_page,
          total: response.meta.total
        }
      } catch (error) {
        if (request !== productsRequest || controller.signal.aborted) return
        this.products = []
        this.productsError = apiErrorMessage(error, 'Não foi possível carregar os produtos.')
      } finally {
        if (request === productsRequest) {
          productsAbortController = undefined
          this.productsLoading = false
        }
      }
    },

    async loadSummary(): Promise<void> {
      const request = ++summaryRequest
      this.summaryLoading = true
      this.summaryError = ''

      try {
        const response = await apiGet<ResourceResponse<InventorySummary>>('/api/v1/dashboard/summary')
        if (request !== summaryRequest) return
        this.summary = response.data
      } catch (error) {
        if (request !== summaryRequest) return
        this.summary = null
        this.summaryError = apiErrorMessage(error, 'Não foi possível carregar o dashboard.')
      } finally {
        if (request === summaryRequest) this.summaryLoading = false
      }
    },

    async createCategory(input: Pick<InventoryCategory, 'name' | 'description' | 'status'>): Promise<InventoryCategory> {
      const response = await apiWrite<ResourceResponse<InventoryCategory>>('/api/v1/categories', 'POST', input)
      return response.data
    },

    async updateCategory(id: string, input: Pick<InventoryCategory, 'name' | 'description' | 'status'>): Promise<InventoryCategory> {
      const response = await apiWrite<ResourceResponse<InventoryCategory>>(`/api/v1/categories/${id}`, 'PATCH', input)
      this.categoryOptions = this.categoryOptions.map(category => category.id === id ? { ...category, ...response.data } : category)
      return response.data
    },

    async createProduct(input: Pick<InventoryProduct, 'category_id' | 'name' | 'description' | 'cost' | 'price' | 'stock' | 'status' | 'image'>): Promise<InventoryProduct> {
      const response = await apiWrite<ResourceResponse<InventoryProduct>>('/api/v1/products', 'POST', input)
      return response.data
    },

    async updateProduct(id: string, input: Pick<InventoryProduct, 'category_id' | 'name' | 'description' | 'cost' | 'price' | 'stock' | 'status' | 'image'>): Promise<InventoryProduct> {
      const response = await apiWrite<ResourceResponse<InventoryProduct>>(`/api/v1/products/${id}`, 'PATCH', input)
      return response.data
    },

    async deleteProduct(id: string): Promise<void> {
      await apiWrite<void>(`/api/v1/products/${id}`, 'DELETE')
    }
  }
})
