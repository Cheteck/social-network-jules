"use client";
import React from 'react';
import { Home, Search, Bell, User, LogOut, PlusCircle } from 'lucide-react';
import Link from 'next/link';

const navItems = [
  { href: '/feed', label: 'Accueil', icon: Home },
  { href: '/search', label: 'Explorer', icon: Search },
  { href: '/notifications', label: 'Notifications', icon: Bell },
  { href: '/profile', label: 'Profil', icon: User },
];

export default function Sidebar() {
  return (
    <nav className="flex flex-col h-full justify-between">
      <div>
        <div className="font-extrabold text-2xl mb-8 px-2">SocialX</div>
        <ul className="space-y-2">
          {navItems.map(({ href, label, icon: Icon }) => (
            <li key={href}>
              <Link href={href} className="flex items-center gap-4 px-3 py-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <Icon className="w-6 h-6" />
                <span className="font-medium text-lg hidden md:inline">{label}</span>
              </Link>
            </li>
          ))}
        </ul>
        <div className="mt-8 flex justify-center">
          <button className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-full shadow transition">
            <PlusCircle className="w-5 h-5" />
            <span className="hidden md:inline">Poster</span>
          </button>
        </div>
      </div>
      <div className="mb-4 px-2">
        <button className="flex items-center gap-2 text-gray-500 hover:text-red-500 transition">
          <LogOut className="w-5 h-5" />
          <span className="hidden md:inline">Déconnexion</span>
        </button>
      </div>
    </nav>
  );
}
