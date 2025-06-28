'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
// Importer des icônes (ex: lucide-react ou SVGs personnalisés)
// import { Home, Search, Bell, Mail, User, Settings } from 'lucide-react';

interface NavItem {
  href: string;
  label: string;
  icon?: React.ReactNode; // ReactNode pour permettre des SVGs ou des composants d'icônes
}

const navItems: NavItem[] = [
  { href: '/main_group/home', label: 'Accueil', icon: <span>🏠</span> }, // Chemin mis à jour
  { href: '/main_group/explore', label: 'Explorer', icon: <span>🔍</span> }, // Chemin mis à jour
  { href: '/main_group/notifications', label: 'Notifications', icon: <span>🔔</span> }, // Chemin mis à jour
  { href: '/main_group/messages', label: 'Messages', icon: <span>✉️</span> }, // Chemin mis à jour
  // { href: '/profile', label: 'Profil', icon: <span>👤</span> }, // Le profil est souvent dynamique
  { href: '/main_group/settings', label: 'Paramètres', icon: <span>⚙️</span> }, // Chemin mis à jour
];

export default function Sidebar() {
  const pathname = usePathname();
  // const { user } = useAuth(); // Pour le lien de profil dynamique

  // const profileLink = user ? `/main_group/${user.username}` : '/auth_group/login'; // Chemin mis à jour
  // const dynamicNavItems = [
  //   ...navItems.filter(item => item.label !== 'Profil'),
  //   { href: profileLink, label: 'Profil', icon: <span>👤</span> },
  // ];
  // Simplifié pour l'instant sans le lien profil dynamique dans la sidebar principale
  const currentNavItems = navItems;


  return (
    <aside className="fixed top-16 -left-64 w-64 h-[calc(100vh-4rem)] bg-x-bg border-r border-x-border p-6 transition-all duration-300 md:left-0 z-40">
      <nav className="flex flex-col gap-y-1">
        {currentNavItems.map((item) => {
          const isActive = pathname === item.href || (item.href !== '/main_group/home' && pathname.startsWith(item.href));
          return (
            <Link
              key={item.label}
              href={item.href}
              className={`flex items-center gap-x-3 py-2 px-3 rounded-full text-lg transition-colors
                ${isActive
                  ? 'font-semibold text-x-primary-text bg-x-card-bg'
                  : 'text-x-secondary-text hover:text-x-primary-text hover:bg-x-card-bg/70'
                }`}
            >
              {item.icon && <span className={isActive ? 'text-x-accent' : ''}>{item.icon}</span>}
              {item.label}
            </Link>
          );
        })}
         {/* Bouton Post */}
        <button className="mt-4 w-full bg-x-accent text-white rounded-full py-3 text-lg font-semibold hover:bg-x-accent-hover transition-colors focus:outline-none focus:ring-2 focus:ring-x-accent focus:ring-offset-2 focus:ring-offset-x-bg">
          Post
        </button>
      </nav>
    </aside>
  );
}
