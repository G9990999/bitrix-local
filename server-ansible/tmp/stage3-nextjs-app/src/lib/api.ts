/**
 * API client for Snipe-IT backend.
 * Configurable via NEXT_PUBLIC_API_URL env variable.
 */

import type {
  Asset,
  License,
  User,
  PaginatedResponse,
  ApiResponse,
  CreateAssetPayload,
  CheckoutPayload,
} from '@/types';

const BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8080/api';

async function request<T>(
  path: string,
  options?: RequestInit,
): Promise<T> {
  const res = await fetch(`${BASE_URL}${path}`, {
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...options?.headers,
    },
    ...options,
  });

  if (!res.ok) {
    const body = await res.text();
    throw new Error(`API ${res.status}: ${body}`);
  }

  return res.json() as Promise<T>;
}

// ── Assets ────────────────────────────────────────────────────

export async function getAssets(params?: {
  search?: string;
  page?: number;
  limit?: number;
  status?: string;
}): Promise<PaginatedResponse<Asset>> {
  const qs = new URLSearchParams();
  if (params?.search) qs.set('search', params.search);
  if (params?.page)   qs.set('page',   String(params.page));
  if (params?.limit)  qs.set('limit',  String(params.limit));
  if (params?.status) qs.set('status', params.status);

  return request<PaginatedResponse<Asset>>(`/assets?${qs}`);
}

export async function getAsset(id: number): Promise<ApiResponse<Asset>> {
  return request<ApiResponse<Asset>>(`/assets/${id}`);
}

export async function createAsset(payload: CreateAssetPayload): Promise<ApiResponse<Asset>> {
  return request<ApiResponse<Asset>>('/assets', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function updateAsset(
  id: number,
  payload: Partial<CreateAssetPayload>,
): Promise<ApiResponse<Asset>> {
  return request<ApiResponse<Asset>>(`/assets/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  });
}

export async function deleteAsset(id: number): Promise<void> {
  await request<void>(`/assets/${id}`, { method: 'DELETE' });
}

// ── Checkout / Checkin ────────────────────────────────────────

export async function checkoutAsset(payload: CheckoutPayload): Promise<void> {
  await request<void>(`/assets/${payload.asset_id}/checkout`, {
    method: 'POST',
    body: JSON.stringify({ user_id: payload.user_id, note: payload.note }),
  });
}

export async function checkinAsset(assetId: number, note?: string): Promise<void> {
  await request<void>(`/assets/${assetId}/checkin`, {
    method: 'POST',
    body: JSON.stringify({ note }),
  });
}

// ── Licenses ─────────────────────────────────────────────────

export async function getLicenses(params?: {
  page?: number;
  limit?: number;
}): Promise<PaginatedResponse<License>> {
  const qs = new URLSearchParams();
  if (params?.page)  qs.set('page',  String(params.page));
  if (params?.limit) qs.set('limit', String(params.limit));

  return request<PaginatedResponse<License>>(`/licenses?${qs}`);
}

export async function getLicense(id: number): Promise<ApiResponse<License>> {
  return request<ApiResponse<License>>(`/licenses/${id}`);
}

// ── Users ─────────────────────────────────────────────────────

export async function getUsers(params?: {
  search?: string;
  page?: number;
  limit?: number;
}): Promise<PaginatedResponse<User>> {
  const qs = new URLSearchParams();
  if (params?.search) qs.set('search', params.search);
  if (params?.page)   qs.set('page',   String(params.page));
  if (params?.limit)  qs.set('limit',  String(params.limit));

  return request<PaginatedResponse<User>>(`/users?${qs}`);
}

export async function getUser(id: number): Promise<ApiResponse<User>> {
  return request<ApiResponse<User>>(`/users/${id}`);
}
