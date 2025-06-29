import LoginForm from '@/components/auth/LoginForm';
import Link from 'next/link'; // Import Link

export default function LoginPage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-x-bg text-x-primary-text p-4">
      <div className="w-full max-w-md space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-bold tracking-tight text-x-primary-text">
            Connectez-vous à votre compte
          </h2>
        </div>
        <LoginForm />
        <p className="mt-10 text-center text-sm text-x-secondary-text">
          Pas encore de compte ?{' '}
          <Link href="/auth_group/register" className="font-medium text-x-accent hover:text-x-accent-hover">
            Inscrivez-vous
          </Link>
        </p>
      </div>
    </div>
  );
}
