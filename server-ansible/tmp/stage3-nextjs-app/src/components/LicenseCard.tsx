import { Card, CardBody, CardHeader, Chip, Progress } from '@heroui/react';
import type { License } from '@/types';

interface LicenseCardProps {
  license: License;
}

export default function LicenseCard({ license }: LicenseCardProps) {
  const used      = license.seats_used      ?? 0;
  const available = license.seats_available ?? license.seats - used;
  const pct       = license.seats > 0 ? Math.round((used / license.seats) * 100) : 0;

  const isExpired = license.expiration_date
    ? new Date(license.expiration_date) < new Date()
    : false;

  const expiringSoon = license.expiration_date && !isExpired
    ? (new Date(license.expiration_date).getTime() - Date.now()) < 30 * 24 * 60 * 60 * 1000
    : false;

  return (
    <Card className="w-full">
      <CardHeader className="flex gap-3 justify-between items-start pb-0">
        <div>
          <p className="text-md font-semibold">{license.name}</p>
          {license.serial && (
            <p className="text-small text-default-400 font-mono">{license.serial}</p>
          )}
        </div>
        <div className="flex gap-1">
          {isExpired && (
            <Chip color="danger" size="sm" variant="flat">Истекла</Chip>
          )}
          {expiringSoon && !isExpired && (
            <Chip color="warning" size="sm" variant="flat">Истекает скоро</Chip>
          )}
        </div>
      </CardHeader>

      <CardBody className="pt-3">
        <div className="flex justify-between text-small text-default-600 mb-1">
          <span>Места: {used} / {license.seats}</span>
          <span className="font-medium text-default-900">
            {available} доступно
          </span>
        </div>

        <Progress
          aria-label="Использование мест"
          value={pct}
          color={pct >= 100 ? 'danger' : pct >= 80 ? 'warning' : 'success'}
          className="mb-3"
          size="sm"
        />

        <div className="flex gap-4 text-small text-default-500">
          {license.purchase_date && (
            <span>Куплено: {new Date(license.purchase_date).toLocaleDateString('ru-RU')}</span>
          )}
          {license.expiration_date && (
            <span
              className={
                isExpired ? 'text-danger font-medium' :
                expiringSoon ? 'text-warning font-medium' : ''
              }
            >
              До: {new Date(license.expiration_date).toLocaleDateString('ru-RU')}
            </span>
          )}
          {license.purchase_cost != null && (
            <span>{license.purchase_cost.toLocaleString('ru-RU')} ₽</span>
          )}
        </div>
      </CardBody>
    </Card>
  );
}
