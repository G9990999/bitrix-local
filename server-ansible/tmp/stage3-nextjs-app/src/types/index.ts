// ============================================================
// Snipe-IT Next.js App — TypeScript Interfaces
// Mirror the data shapes returned by the backend REST API.
// ============================================================

export type AssetStatus = 'deployable' | 'pending' | 'archived' | 'undeployable';

export interface Asset {
  id: number;
  name: string | null;
  asset_tag: string | null;
  serial: string | null;
  model_id: number | null;
  model?: AssetModel;
  status: AssetStatus;
  status_label?: StatusLabel;
  purchase_date: string | null;    // ISO date string
  purchase_cost: number | null;
  order_number: string | null;
  assigned_to: number | null;
  assigned_user?: User;
  company_id: number | null;
  company?: Company;
  location_id: number | null;
  location?: Location;
  warranty_months: number | null;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface AssetModel {
  id: number;
  name: string;
  manufacturer_id: number | null;
  manufacturer?: Manufacturer;
  category_id: number | null;
  category?: Category;
  model_number: string | null;
}

export interface User {
  id: number;
  first_name: string | null;
  last_name: string | null;
  username: string;
  email: string | null;
  employee_num: string | null;
  jobtitle: string | null;
  phone: string | null;
  company_id: number | null;
  location_id: number | null;
}

export interface License {
  id: number;
  name: string;
  serial: string | null;
  seats: number;
  seats_used?: number;
  seats_available?: number;
  purchase_date: string | null;
  purchase_cost: number | null;
  expiration_date: string | null;
  company_id: number | null;
  company?: Company;
  notes: string | null;
  created_at: string | null;
}

export interface StatusLabel {
  id: number;
  name: string;
  deployable: boolean;
  pending: boolean;
  archived: boolean;
  color: string | null;
}

export interface Company {
  id: number;
  name: string;
}

export interface Location {
  id: number;
  name: string;
  city: string | null;
  country: string | null;
}

export interface Category {
  id: number;
  name: string;
  category_type: string;
}

export interface Manufacturer {
  id: number;
  name: string;
  url: string | null;
}

// API response wrappers
export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  per_page: number;
}

export interface ApiResponse<T> {
  data: T;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

// Form payloads
export interface CreateAssetPayload {
  name?: string;
  asset_tag: string;
  serial?: string;
  model_id?: number;
  purchase_date?: string;
  purchase_cost?: number;
  order_number?: string;
  status?: AssetStatus;
  company_id?: number;
  location_id?: number;
  warranty_months?: number;
  notes?: string;
}

export interface CheckoutPayload {
  asset_id: number;
  user_id: number;
  note?: string;
}
