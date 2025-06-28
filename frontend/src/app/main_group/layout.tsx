import Navbar from '@/components/layout/Navbar';
import Sidebar from '@/components/layout/Sidebar';
import React from 'react';
import ProtectedRouteWrapper from '@/components/auth/ProtectedRouteWrapper';

export default function MainGroupLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <ProtectedRouteWrapper>
      <div className="min-h-screen bg-x-bg text-x-primary-text">
        <Navbar />
        <div className="flex pt-16"> {/* pt-16 pour compenser la hauteur de la Navbar fixe */}
          <Sidebar />
          <main className="flex-grow md:ml-64 transition-all duration-300 p-4 sm:p-6 lg:p-8">
            {/* Le ml-64 sur md et plus correspond à la largeur de la Sidebar */}
            {/* La gestion du "left-0" ou "-left-64" de la sidebar pour mobile
                et le décalage du main content sur mobile sera gérée par JS dans la Sidebar/Navbar plus tard si besoin.
                Pour l'instant, la sidebar sera toujours visible sur md+ et cachée sur mobile.
            */}
            {children}
          </main>
        </div>
      </div>
    </ProtectedRouteWrapper>
  );
}
