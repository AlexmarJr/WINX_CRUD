import { defineStore } from 'pinia'

export interface InventoryCategory {
  id: string
  tenancy_id: string
  name: string
  description: string | null
  status: 'active' | 'inactive'
  meta: Record<string, unknown> | null
  deleted_at: string | null
  user_id: string
  created_at: string
  updated_at: string
}

export interface InventoryProduct {
  id: string
  tenancy_id: string
  category_id: string
  name: string
  description: string | null
  cost: number | null
  price: number
  stock: number
  status: 'active' | 'inactive'
  user_id: string
  meta: Record<string, unknown> | null
  deleted_at: string | null
  image: string | null
  created_at: string
  updated_at: string
}

const tenancyId = 'b0dc4e15-5010-4b72-9100-000000000001'
const userId = 'b0dc4e15-5010-4b72-9100-000000000002'
const categoryIds = {
  peripherals: 'b0dc4e15-5010-4b72-9100-000000001001',
  monitors: 'b0dc4e15-5010-4b72-9100-000000001002',
  audio: 'b0dc4e15-5010-4b72-9100-000000001003',
  accessories: 'b0dc4e15-5010-4b72-9100-000000001004',
  video: 'b0dc4e15-5010-4b72-9100-000000001005'
}

const categories: InventoryCategory[] = [
  { id: categoryIds.peripherals, tenancy_id: tenancyId, name: 'Periféricos', description: 'Mouses, teclados e acessórios de entrada.', status: 'active', meta: { department: 'Tecnologia' }, deleted_at: null, user_id: userId, created_at: '2026-09-01T09:00:00Z', updated_at: '2026-09-01T09:00:00Z' },
  { id: categoryIds.monitors, tenancy_id: tenancyId, name: 'Monitores', description: 'Telas e displays para estações de trabalho.', status: 'active', meta: null, deleted_at: null, user_id: userId, created_at: '2026-09-02T09:00:00Z', updated_at: '2026-09-02T09:00:00Z' },
  { id: categoryIds.audio, tenancy_id: tenancyId, name: 'Áudio', description: 'Headsets, caixas de som e microfones.', status: 'active', meta: null, deleted_at: null, user_id: userId, created_at: '2026-09-03T09:00:00Z', updated_at: '2026-09-03T09:00:00Z' },
  { id: categoryIds.accessories, tenancy_id: tenancyId, name: 'Acessórios', description: 'Cabos, adaptadores e suportes.', status: 'active', meta: null, deleted_at: null, user_id: userId, created_at: '2026-09-04T09:00:00Z', updated_at: '2026-09-04T09:00:00Z' },
  { id: categoryIds.video, tenancy_id: tenancyId, name: 'Vídeo', description: 'Câmeras e equipamentos de vídeo.', status: 'inactive', meta: null, deleted_at: null, user_id: userId, created_at: '2026-09-05T09:00:00Z', updated_at: '2026-09-05T09:00:00Z' }
]

const products: InventoryProduct[] = [
  { id: 'b0dc4e15-5010-4b72-9100-000000002001', tenancy_id: tenancyId, category_id: categoryIds.peripherals, name: 'Mouse sem fio M185', description: 'Mouse sem fio com conexão USB e bateria inclusa.', cost: 49.90, price: 89.90, stock: 2, status: 'active', user_id: userId, meta: { sku: 'MOU-185' }, deleted_at: null, image: null, created_at: '2026-09-10T10:00:00Z', updated_at: '2026-09-22T14:30:00Z' },
  { id: 'b0dc4e15-5010-4b72-9100-000000002002', tenancy_id: tenancyId, category_id: categoryIds.peripherals, name: 'Teclado mecânico K500', description: 'Teclado mecânico compacto para uso diário.', cost: 159.00, price: 249.00, stock: 3, status: 'active', user_id: userId, meta: { sku: 'TEC-K500' }, deleted_at: null, image: null, created_at: '2026-09-11T10:00:00Z', updated_at: '2026-09-21T11:00:00Z' },
  { id: 'b0dc4e15-5010-4b72-9100-000000002003', tenancy_id: tenancyId, category_id: categoryIds.monitors, name: 'Monitor LED 24"', description: 'Monitor Full HD de 24 polegadas.', cost: 620.00, price: 899.00, stock: 5, status: 'active', user_id: userId, meta: { sku: 'MON-24' }, deleted_at: null, image: null, created_at: '2026-09-12T10:00:00Z', updated_at: '2026-09-20T10:00:00Z' },
  { id: 'b0dc4e15-5010-4b72-9100-000000002004', tenancy_id: tenancyId, category_id: categoryIds.audio, name: 'Headset USB Pro', description: 'Headset com microfone e conexão USB.', cost: 115.00, price: 189.00, stock: 7, status: 'active', user_id: userId, meta: { sku: 'AUD-PRO' }, deleted_at: null, image: null, created_at: '2026-09-13T10:00:00Z', updated_at: '2026-09-19T10:00:00Z' },
  { id: 'b0dc4e15-5010-4b72-9100-000000002005', tenancy_id: tenancyId, category_id: categoryIds.video, name: 'Webcam Full HD', description: 'Webcam para reuniões e transmissões.', cost: 180.00, price: 299.00, stock: 8, status: 'inactive', user_id: userId, meta: { sku: 'VID-FHD' }, deleted_at: null, image: null, created_at: '2026-09-14T10:00:00Z', updated_at: '2026-09-18T10:00:00Z' },
  { id: 'b0dc4e15-5010-4b72-9100-000000002006', tenancy_id: tenancyId, category_id: categoryIds.accessories, name: 'Cabo HDMI 2 m', description: 'Cabo HDMI de alta velocidade.', cost: 18.00, price: 39.90, stock: 9, status: 'active', user_id: userId, meta: { sku: 'CAB-HDMI-2' }, deleted_at: null, image: null, created_at: '2026-09-15T10:00:00Z', updated_at: '2026-09-17T10:00:00Z' }
]

export const useInventoryStore = defineStore('inventory', {
  state: () => ({
    categories: categories.map(category => ({ ...category })),
    products: products.map(product => ({ ...product }))
  }),
  actions: {
    addCategory(input: Pick<InventoryCategory, 'name' | 'description' | 'status' | 'meta'>): void {
      const now = new Date().toISOString()
      this.categories.unshift({ id: crypto.randomUUID(), tenancy_id: tenancyId, user_id: userId, deleted_at: null, created_at: now, updated_at: now, ...input })
    },
    addProduct(input: Pick<InventoryProduct, 'category_id' | 'name' | 'description' | 'cost' | 'price' | 'stock' | 'status' | 'meta' | 'image'>): void {
      const now = new Date().toISOString()
      this.products.unshift({ id: crypto.randomUUID(), tenancy_id: tenancyId, user_id: userId, deleted_at: null, created_at: now, updated_at: now, ...input })
    },
    removeProduct(id: string): void {
      this.products = this.products.filter(product => product.id !== id)
    }
  }
})
