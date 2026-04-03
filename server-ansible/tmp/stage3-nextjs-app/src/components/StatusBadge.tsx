import { Chip, ChipProps } from '@heroui/react';
import type { AssetStatus } from '@/types';

const statusConfig: Record<AssetStatus, { label: string; color: ChipProps['color'] }> = {
  deployable:   { label: 'Развёрнут',  color: 'success'  },
  pending:      { label: 'Ожидание',   color: 'warning'  },
  archived:     { label: 'Архив',      color: 'default'  },
  undeployable: { label: 'Недоступен', color: 'danger'   },
};

interface StatusBadgeProps {
  status: AssetStatus;
  size?: ChipProps['size'];
}

export default function StatusBadge({ status, size = 'sm' }: StatusBadgeProps) {
  const config = statusConfig[status] ?? { label: status, color: 'default' };

  return (
    <Chip color={config.color} size={size} variant="flat">
      {config.label}
    </Chip>
  );
}
