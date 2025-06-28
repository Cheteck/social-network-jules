'use client';

import Link from 'next/link';
import { useAuth } from '@/lib/contexts/AuthContext';
// import { MenuIcon } from 'lucide-react'; // Ou un autre icône SVG/composant

export default function Navbar() {
  const { user, logout } = useAuth();

  // TODO: Ajouter la logique pour ouvrir/fermer la sidebar sur mobile
  // const toggleMobileSidebar = () => {
  //   console.log('Toggle mobile sidebar');
  //   // Dispatch un événement ou appeler une fonction d'un contexte de layout
  // };

  return (
    <nav className="fixed top-0 left-0 right-0 bg-x-bg border-b border-x-border p-4 flex justify-between items-center z-50 h-16">
      <Link href={user ? "/main_group/home" : "/"} className="text-2xl font-bold text-x-accent hover:opacity-80 transition-opacity">
        X
      </Link>

      <div className="flex items-center space-x-4">
        {user ? (
          <>
            <Link href={`/main_group/${user.username || user.id}`} className="text-sm text-x-primary-text hover:text-x-accent transition-colors">
              Profil ({user.name})
            </Link>
            <button
              onClick={logout}
              className="text-sm text-x-secondary-text hover:text-x-accent transition-colors"
            >
              Déconnexion
            </button>
            {/* <div className="text-sm text-x-primary-text">Bienvenue, {user.name}</div> */}
          </>
        ) : (
          <>
            <Link href="/auth_group/login" className="text-sm font-medium text-x-primary-text hover:text-x-accent transition-colors">
              Connexion
            </Link>
            <Link
              href="/auth_group/register"
              className="text-sm font-medium bg-x-accent text-white px-4 py-2 rounded-full hover:bg-x-accent-hover transition-colors"
            >
              Inscription
            </Link>
          </>
        )}
      </div>

      {/* Icône Menu pour mobile - temporairement masqué ou à styler/fonctionnaliser */}
      {/* <div className="md:hidden">
        <button onClick={toggleMobileSidebar} className="text-x-primary-text">
          <MenuIcon size={24} />
        </button>
      </div> */}
    </nav>
  );
}
