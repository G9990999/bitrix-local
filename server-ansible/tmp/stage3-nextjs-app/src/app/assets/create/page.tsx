'use client';

import {
  Card,
  CardBody,
  CardHeader,
  Input,
  Textarea,
  Button,
  Select,
  SelectItem,
} from '@heroui/react';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { createAsset } from '@/lib/api';
import type { AssetStatus } from '@/types';

const statusOptions: { value: AssetStatus; label: string }[] = [
  { value: 'pending',      label: 'Ожидание'   },
  { value: 'deployable',   label: 'Развёрнут'  },
  { value: 'archived',     label: 'Архив'      },
  { value: 'undeployable', label: 'Недоступен' },
];

export default function CreateAssetPage() {
  const router = useRouter();

  const [form, setForm] = useState({
    asset_tag:     '',
    name:          '',
    serial:        '',
    purchase_date: '',
    purchase_cost: '',
    order_number:  '',
    status:        'pending' as AssetStatus,
    warranty_months: '',
    notes:         '',
  });

  const [loading, setLoading] = useState(false);
  const [error, setError]     = useState<string | null>(null);

  const set = (field: string, value: string) =>
    setForm((prev) => ({ ...prev, [field]: value }));

  const handleSubmit = async () => {
    if (!form.asset_tag.trim()) {
      setError('Тег актива обязателен');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const payload = {
        asset_tag:       form.asset_tag,
        name:            form.name || undefined,
        serial:          form.serial || undefined,
        purchase_date:   form.purchase_date || undefined,
        purchase_cost:   form.purchase_cost ? parseFloat(form.purchase_cost) : undefined,
        order_number:    form.order_number || undefined,
        status:          form.status,
        warranty_months: form.warranty_months ? parseInt(form.warranty_months, 10) : undefined,
        notes:           form.notes || undefined,
      };

      const res = await createAsset(payload);
      router.push(`/assets/${res.data.id}`);
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Ошибка создания актива');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Новый актив</h1>

      <Card className="max-w-2xl">
        <CardHeader>
          <p className="text-sm text-default-500">Поля, отмеченные *, обязательны</p>
        </CardHeader>
        <CardBody className="flex flex-col gap-4">
          {error && (
            <div className="bg-danger-50 text-danger rounded-lg p-3 text-sm">
              {error}
            </div>
          )}

          <Input
            label="Тег актива *"
            placeholder="Например: HW-0042"
            value={form.asset_tag}
            onValueChange={(v) => set('asset_tag', v)}
            isRequired
          />
          <Input
            label="Название"
            placeholder="Например: ThinkPad X1 Carbon"
            value={form.name}
            onValueChange={(v) => set('name', v)}
          />
          <Input
            label="Серийный номер"
            value={form.serial}
            onValueChange={(v) => set('serial', v)}
          />

          <div className="grid grid-cols-2 gap-4">
            <Input
              label="Дата покупки"
              type="date"
              value={form.purchase_date}
              onValueChange={(v) => set('purchase_date', v)}
            />
            <Input
              label="Стоимость (₽)"
              type="number"
              min="0"
              step="0.01"
              value={form.purchase_cost}
              onValueChange={(v) => set('purchase_cost', v)}
            />
          </div>

          <div className="grid grid-cols-2 gap-4">
            <Input
              label="Номер заказа"
              value={form.order_number}
              onValueChange={(v) => set('order_number', v)}
            />
            <Input
              label="Гарантия (месяцев)"
              type="number"
              min="0"
              value={form.warranty_months}
              onValueChange={(v) => set('warranty_months', v)}
            />
          </div>

          <Select
            label="Статус"
            selectedKeys={[form.status]}
            onSelectionChange={(keys) => {
              const v = Array.from(keys)[0] as AssetStatus;
              if (v) set('status', v);
            }}
          >
            {statusOptions.map((opt) => (
              <SelectItem key={opt.value}>{opt.label}</SelectItem>
            ))}
          </Select>

          <Textarea
            label="Примечания"
            value={form.notes}
            onValueChange={(v) => set('notes', v)}
            minRows={3}
          />

          <div className="flex gap-3">
            <Button
              color="primary"
              onPress={handleSubmit}
              isLoading={loading}
            >
              Сохранить
            </Button>
            <Button variant="flat" onPress={() => router.back()}>
              Отмена
            </Button>
          </div>
        </CardBody>
      </Card>
    </div>
  );
}
