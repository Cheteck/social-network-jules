'use client'; // Required for useParams

import ResetPasswordForm from '@/components/auth/ResetPasswordForm';
import { useParams } from 'next/navigation'; // To get token from URL
import Link from 'next/link';

export default function ResetPasswordPage() {
  const params = useParams();
  const token = typeof params.token === 'string' ? params.token : '';

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-x-bg text-x-primary-text p-4">
      <div className="w-full max-w-md space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-bold tracking-tight">
            Réinitialiser votre mot de passe
          </h2>
        </div>
        {token ? (
          <ResetPasswordForm token={token} />
        ) : (
          <div className="text-center">
            <p className="text-red-500">Jeton de réinitialisation invalide ou manquant.</p>
            <p className="mt-4 text-sm text-x-secondary-text">
              Veuillez demander un nouveau{' '}
              <Link href="/auth_group/forgot-password" className="font-medium text-x-accent hover:text-x-accent-hover">
                lien de réinitialisation
              </Link>.
            </p>
          </div>
        )}
        <p className="mt-10 text-center text-sm text-x-secondary-text">
          <Link href="/auth_group/login" className="font-medium text-x-accent hover:text-x-accent-hover">
            Retour à la connexion
          </Link>
        </p>
      </div>
    </div>
  );
}
