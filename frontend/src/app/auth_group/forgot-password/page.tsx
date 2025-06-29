import ForgotPasswordForm from '@/components/auth/ForgotPasswordForm';
import Link from 'next/link';

export default function ForgotPasswordPage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-x-bg text-x-primary-text p-4">
      <div className="w-full max-w-md space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-bold tracking-tight text-x-primary-text">
            Mot de passe oublié ?
          </h2>
          <p className="mt-2 text-center text-sm text-x-secondary-text">
            Entrez votre adresse e-mail et nous vous enverrons un lien pour réinitialiser votre mot de passe.
          </p>
        </div>
        <ForgotPasswordForm />
        <p className="mt-10 text-center text-sm text-x-secondary-text">
          Vous vous souvenez de votre mot de passe ?{' '}
          <Link href="/auth_group/login" className="font-medium text-x-accent hover:text-x-accent-hover">
            Se connecter
          </Link>
        </p>
      </div>
    </div>
  );
}
