import { Card, CardBody, CardHeader } from '@heroui/react';
import Link from 'next/link';

const quickLinks = [
  { href: '/assets',          label: 'Активы',       icon: '🖥',  desc: 'Управление оборудованием' },
  { href: '/licenses',        label: 'Лицензии',     icon: '📄',  desc: 'Учёт ПО и лицензий'      },
  { href: '/users',           label: 'Пользователи', icon: '👤',  desc: 'Список сотрудников'       },
  { href: '/assets/create',   label: 'Новый актив',  icon: '➕',  desc: 'Добавить оборудование'    },
];

export default function DashboardPage() {
  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Дашборд</h1>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {quickLinks.map((link) => (
          <Link key={link.href} href={link.href}>
            <Card className="hover:shadow-md transition-shadow cursor-pointer h-full">
              <CardHeader className="pb-0">
                <span className="text-3xl">{link.icon}</span>
              </CardHeader>
              <CardBody className="pt-2">
                <p className="font-semibold text-lg">{link.label}</p>
                <p className="text-small text-default-500">{link.desc}</p>
              </CardBody>
            </Card>
          </Link>
        ))}
      </div>

      <div className="mt-8">
        <h2 className="text-lg font-semibold mb-3">О системе</h2>
        <Card>
          <CardBody className="text-sm text-default-600 space-y-2">
            <p>
              <strong>Snipe-IT Asset Management</strong> — система учёта IT-активов.
              Позволяет отслеживать оборудование, лицензии ПО и расходные материалы.
            </p>
            <p>
              Этот интерфейс реализован на <strong>Next.js 14</strong> с компонентами{' '}
              <strong>HeroUI</strong> (бывший NextUI) и подключается к REST API бэкенда.
            </p>
          </CardBody>
        </Card>
      </div>
    </div>
  );
}
