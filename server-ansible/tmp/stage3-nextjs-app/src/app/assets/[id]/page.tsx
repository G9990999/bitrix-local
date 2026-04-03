'use client';

import {
  Card,
  CardBody,
  CardHeader,
  Button,
  Divider,
  useDisclosure,
} from '@heroui/react';
import { useEffect, useState, use } from 'react';
import Link from 'next/link';
import StatusBadge from '@/components/StatusBadge';
import CheckoutModal from '@/components/CheckoutModal';
import { getAsset, deleteAsset } from '@/lib/api';
import type { Asset } from '@/types';
import { useRouter } from 'next/navigation';

interface PageParams { id: string }

export default function AssetDetailPage({ params }: { params: Promise<PageParams> }) {
  const { id } = use(params);
  const assetId = parseInt(id, 10);

  const router = useRouter();
  const { isOpen, onOpen, onClose } = useDisclosure();

  const [asset, setAsset]   = useState<Asset | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState<string | null>(null);

  const load = async () => {
    setLoading(true);
    try {
      const res = await getAsset(assetId);
      setAsset(res.data);
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Не удалось загрузить актив');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { load(); }, [assetId]);

  const handleDelete = async () => {
    if (!confirm('Удалить этот актив?')) return;
    await deleteAsset(assetId);
    router.push('/assets');
  };

  if (loading) return <p className="text-center py-12 text-default-400">Загрузка…</p>;
  if (error)   return <p className="text-center py-12 text-danger">{error}</p>;
  if (!asset)  return <p className="text-center py-12 text-default-400">Актив не найден</p>;

  return (
    <div>
      <div className="flex items-center gap-3 mb-6">
        <Button as={Link} href="/assets" variant="light" size="sm">← Назад</Button>
        <h1 className="text-2xl font-bold flex-1">
          {asset.name ?? asset.asset_tag ?? `Актив #${asset.id}`}
        </h1>
        <StatusBadge status={asset.status} size="md" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {/* Main info */}
        <Card className="lg:col-span-2">
          <CardHeader><p className="font-semibold">Основная информация</p></CardHeader>
          <Divider />
          <CardBody>
            <dl className="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
              {[
                { label: 'Тег актива',     value: asset.asset_tag },
                { label: 'Серийный номер', value: asset.serial },
                { label: 'Номер заказа',   value: asset.order_number },
                { label: 'Дата покупки',   value: asset.purchase_date },
                { label: 'Стоимость',
                  value: asset.purchase_cost != null
                    ? `${asset.purchase_cost.toLocaleString('ru-RU')} ₽`
                    : null },
                { label: 'Гарантия',
                  value: asset.warranty_months ? `${asset.warranty_months} мес.` : null },
                { label: 'Назначен',
                  value: asset.assigned_to ? `Пользователь #${asset.assigned_to}` : '—' },
                { label: 'Добавлен',   value: asset.created_at },
                { label: 'Обновлён',   value: asset.updated_at },
              ].map(({ label, value }) => (
                <>
                  <dt key={`l-${label}`} className="text-default-500 font-medium">{label}</dt>
                  <dd key={`v-${label}`}>{value ?? '—'}</dd>
                </>
              ))}
            </dl>

            {asset.notes && (
              <div className="mt-4 p-3 bg-default-50 rounded-lg text-sm whitespace-pre-wrap">
                {asset.notes}
              </div>
            )}
          </CardBody>
        </Card>

        {/* Actions */}
        <Card>
          <CardHeader><p className="font-semibold">Действия</p></CardHeader>
          <Divider />
          <CardBody className="flex flex-col gap-3">
            <Button
              color={asset.assigned_to ? 'warning' : 'success'}
              fullWidth
              onPress={onOpen}
            >
              {asset.assigned_to ? 'Принять актив' : 'Выдать актив'}
            </Button>

            <Button
              as={Link}
              href={`/assets/${asset.id}/edit`}
              variant="flat"
              fullWidth
            >
              Редактировать
            </Button>

            <Button
              color="danger"
              variant="flat"
              fullWidth
              onPress={handleDelete}
            >
              Удалить
            </Button>
          </CardBody>
        </Card>
      </div>

      {asset && (
        <CheckoutModal
          asset={asset}
          isOpen={isOpen}
          onClose={onClose}
          onSuccess={load}
        />
      )}
    </div>
  );
}
