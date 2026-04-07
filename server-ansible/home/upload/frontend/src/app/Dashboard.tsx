import { Card, CardHeader, CardBody, Chip } from '@heroui/react'
import { Link } from 'react-router-dom'

const sections = [
  {
    href:        '/assets',
    title:       'Активы',
    description: 'Учёт ИТ-оборудования: ноутбуки, серверы, периферия.',
    color:       'primary' as const,
    icon:        '🖥️',
  },
  {
    href:        '/licenses',
    title:       'Лицензии',
    description: 'Программное обеспечение, лицензионные ключи, места.',
    color:       'secondary' as const,
    icon:        '🔑',
  },
  {
    href:        '/users',
    title:       'Пользователи',
    description: 'Сотрудники Bitrix24 и назначенные им активы.',
    color:       'success' as const,
    icon:        '👤',
  },
  {
    href:        '/roles',
    title:       'Роли и доступы',
    description: 'Каталог должностей с матрицей прав доступа.',
    color:       'warning' as const,
    icon:        '🛡️',
  },
  {
    href:        '/audit',
    title:       'Аудит',
    description: 'Кто и к чему имеет доступ. История выдачи и возврата.',
    color:       'danger' as const,
    icon:        '📋',
  },
  {
    href:        '/events',
    title:       'События',
    description: 'События в системе. Подписка на сервисы.',
    color:       'danger' as const,
    icon:        '📋',
  },
]

export default function Dashboard() {
  return (
    <div>
      <h1 className="text-2xl font-bold mb-2">Панель администратора Bitrix24</h1>
      <p className="text-gray-500 mb-6">
        Управление активами (SnipeIT), лицензиями и ролями доступа.
      </p>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {sections.map((section) => (
          <Link key={section.href} to={section.href} className="no-underline">
            <Card isPressable isHoverable className="h-full">
              <CardHeader className="flex gap-3 items-center">
                <span className="text-3xl">{section.icon}</span>
                <div>
                  <p className="text-lg font-semibold">{section.title}</p>
                  <Chip size="sm" color={section.color} variant="flat">
                    {section.href.replace('/', '') || 'home'}
                  </Chip>
                </div>
              </CardHeader>
              <CardBody>
                <p className="text-sm text-gray-600 dark:text-gray-400">
                  {section.description}
                </p>
              </CardBody>
            </Card>
          </Link>
        ))}
      </div>

      <div className="mt-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <h2 className="font-semibold mb-1">Быстрые команды CLI</h2>
        <pre className="text-sm text-gray-700 dark:text-gray-300 overflow-x-auto">
{`docker exec bitrix-php bx bx:health
docker exec bitrix-php bx bx:parser tender-bot
docker exec bitrix-php bx bx:role install
docker exec bitrix-php bx bx:role generator`}
        </pre>
      </div>
    </div>
  )
}
