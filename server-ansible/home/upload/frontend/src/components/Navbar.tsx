import { Navbar as HeroNavbar, NavbarBrand, NavbarContent, NavbarItem, Link, Button } from '@heroui/react'
import { useLocation } from 'react-router-dom'

const navItems = [
  { href: '/',         label: 'Дашборд' },
  { href: '/assets',   label: 'Активы' },
  { href: '/licenses', label: 'Лицензии' },
  { href: '/users',    label: 'Пользователи' },
  { href: '/roles',    label: 'Роли доступа' },
  { href: '/audit',    label: 'Аудит' },
  { href: '/events',   label: 'События' },
]

export default function Navbar() {
  const { pathname } = useLocation()

  return (
    <HeroNavbar isBordered>
      <NavbarBrand>
        <span className="font-bold text-lg text-primary">Bitrix Admin</span>
      </NavbarBrand>
      <NavbarContent className="hidden sm:flex gap-4" justify="center">
        {navItems.map((item) => (
          <NavbarItem key={item.href} isActive={pathname === item.href}>
            <Link
              href={item.href}
              color={pathname === item.href ? 'primary' : 'foreground'}
            >
              {item.label}
            </Link>
          </NavbarItem>
        ))}
      </NavbarContent>
      <NavbarContent justify="end">
        <NavbarItem>
          <Button as={Link} href="/api/health" variant="flat" size="sm" color="success">
            Health
          </Button>
        </NavbarItem>
      </NavbarContent>
    </HeroNavbar>
  )
}
