'use client';

import { useState } from 'react';
import {
  Modal,
  ModalContent,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
  Input,
  Textarea,
} from '@heroui/react';
import { checkoutAsset, checkinAsset } from '@/lib/api';
import type { Asset } from '@/types';

interface CheckoutModalProps {
  asset: Asset;
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

export default function CheckoutModal({
  asset,
  isOpen,
  onClose,
  onSuccess,
}: CheckoutModalProps) {
  const [userId, setUserId]   = useState('');
  const [note, setNote]       = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError]     = useState<string | null>(null);

  const isCheckedOut = !!asset.assigned_to;

  const handleSubmit = async () => {
    setLoading(true);
    setError(null);
    try {
      if (isCheckedOut) {
        await checkinAsset(asset.id, note);
      } else {
        const uid = parseInt(userId, 10);
        if (!uid) {
          setError('Введите корректный ID пользователя');
          setLoading(false);
          return;
        }
        await checkoutAsset({ asset_id: asset.id, user_id: uid, note });
      }
      onSuccess();
      onClose();
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Ошибка запроса');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal isOpen={isOpen} onClose={onClose} size="md">
      <ModalContent>
        <ModalHeader>
          {isCheckedOut ? 'Принять актив' : 'Выдать актив'}: {asset.name ?? asset.asset_tag}
        </ModalHeader>
        <ModalBody>
          {error && (
            <div className="bg-danger-50 text-danger rounded-lg p-3 text-sm mb-2">
              {error}
            </div>
          )}

          {isCheckedOut ? (
            <p className="text-sm text-default-600">
              Актив назначен пользователю <strong>#{asset.assigned_to}</strong>.
              Нажмите «Принять», чтобы вернуть его.
            </p>
          ) : (
            <Input
              label="ID пользователя"
              placeholder="Введите числовой ID пользователя"
              type="number"
              value={userId}
              onValueChange={setUserId}
              isRequired
            />
          )}

          <Textarea
            label="Примечание"
            placeholder="Необязательное примечание…"
            value={note}
            onValueChange={setNote}
            className="mt-2"
          />
        </ModalBody>
        <ModalFooter>
          <Button variant="light" onPress={onClose}>
            Отмена
          </Button>
          <Button
            color={isCheckedOut ? 'warning' : 'success'}
            onPress={handleSubmit}
            isLoading={loading}
          >
            {isCheckedOut ? 'Принять' : 'Выдать'}
          </Button>
        </ModalFooter>
      </ModalContent>
    </Modal>
  );
}
