'use client';

import {
  Table,
  TableHeader,
  TableColumn,
  TableBody,
  TableRow,
  TableCell,
  Input,
  Button,
  Pagination,
  Spinner,
} from '@heroui/react';
import { useState, useCallback, useEffect } from 'react';
import Link from 'next/link';
import StatusBadge from './StatusBadge';
import { getAssets } from '@/lib/api';
import type { Asset } from '@/types';

const COLUMNS = [
  { key: 'asset_tag', label: 'Тег' },
  { key: 'name',      label: 'Название' },
  { key: 'serial',    label: 'Серийный №' },
  { key: 'status',    label: 'Статус' },
  { key: 'assigned',  label: 'Назначен' },
  { key: 'actions',   label: 'Действия' },
];

export default function AssetTable() {
  const [assets, setAssets]       = useState<Asset[]>([]);
  const [total, setTotal]         = useState(0);
  const [page, setPage]           = useState(1);
  const [search, setSearch]       = useState('');
  const [loading, setLoading]     = useState(false);
  const perPage = 25;

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await getAssets({ search, page, limit: perPage });
      setAssets(res.data);
      setTotal(res.total);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  }, [search, page]);

  useEffect(() => { load(); }, [load]);

  const renderCell = (asset: Asset, key: string) => {
    switch (key) {
      case 'asset_tag':
        return <span className="font-mono text-sm">{asset.asset_tag ?? '—'}</span>;
      case 'name':
        return (
          <Link href={`/assets/${asset.id}`} className="text-primary hover:underline font-medium">
            {asset.name ?? '—'}
          </Link>
        );
      case 'serial':
        return <span className="text-default-500 text-sm">{asset.serial ?? '—'}</span>;
      case 'status':
        return <StatusBadge status={asset.status} />;
      case 'assigned':
        return asset.assigned_to
          ? <span className="text-sm">#{asset.assigned_to}</span>
          : <span className="text-default-400 text-sm">—</span>;
      case 'actions':
        return (
          <Button
            as={Link}
            href={`/assets/${asset.id}`}
            size="sm"
            variant="flat"
            color="primary"
          >
            Открыть
          </Button>
        );
      default:
        return null;
    }
  };

  return (
    <div className="flex flex-col gap-4">
      <div className="flex gap-3 items-center">
        <Input
          className="max-w-xs"
          placeholder="Поиск по тегу, названию, серийному №…"
          value={search}
          onValueChange={(v) => { setSearch(v); setPage(1); }}
          isClearable
          onClear={() => setSearch('')}
        />
        <Button onPress={load} variant="flat" isLoading={loading}>
          Обновить
        </Button>
        <Button as={Link} href="/assets/create" color="primary">
          + Добавить актив
        </Button>
      </div>

      <Table
        aria-label="Таблица активов"
        bottomContent={
          total > perPage ? (
            <div className="flex justify-center">
              <Pagination
                total={Math.ceil(total / perPage)}
                page={page}
                onChange={setPage}
                color="primary"
              />
            </div>
          ) : null
        }
      >
        <TableHeader columns={COLUMNS}>
          {(col) => <TableColumn key={col.key}>{col.label}</TableColumn>}
        </TableHeader>
        <TableBody
          items={assets}
          isLoading={loading}
          loadingContent={<Spinner label="Загрузка…" />}
          emptyContent="Активов не найдено"
        >
          {(asset) => (
            <TableRow key={asset.id}>
              {(key) => <TableCell>{renderCell(asset, String(key))}</TableCell>}
            </TableRow>
          )}
        </TableBody>
      </Table>

      <p className="text-small text-default-400">Всего: {total}</p>
    </div>
  );
}
