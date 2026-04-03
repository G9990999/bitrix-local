'use client';

import {
  Navbar,
  NavbarBrand,
  NavbarContent,
  NavbarItem,
  Link,
  Button,
} from '@heroui/react';
import { usePathname } from 'next/navigation';

const navItems = [
  { href: '/',         label: 'Дашборд'    },
  { href: '/assets',   label: 'Активы'     },
  { href: '/licenses', label: 'Лицензии'   },
  { href: '/users',    label: 'Пользователи' },
];

export default function AppNavbar() {
  const pathname = usePathname();

  return (
    <Navbar
      isBordered
      classNames={{
        wrapper: 'max-w-7xl',
        item: [
          'flex',
          'relative',
          'h-full',
          'items-center',
          "data-[active=true]:after:content-['']",
          'data-[active=true]:after:absolute',
          'data-[active=true]:after:bottom-0',
          'data-[active=true]:after:left-0',
          'data-[active=true]:after:right-0',
          'data-[active=true]:after:h-[2px]',
          'data-[active=true]:after:rounded-[2px]',
          'data-[active=true]:after:bg-primary',
        ],
      }}
    >
      <NavbarBrand>
        <p className="font-bold text-inherit text-lg">Snipe-IT</p>
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
          <Button
            as={Link}
            href="/assets/create"
            color="primary"
            variant="flat"
            size="sm"
          >
            + Добавить актив
          </Button>
        </NavbarItem>
      </NavbarContent>
    </Navbar>
  );
}
