import RegisterForm from '@/components/auth/RegisterForm'; // Sera créé ensuite

export default function RegisterPage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-x-bg text-x-primary-text p-4">
      <div className="w-full max-w-md space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-bold tracking-tight text-x-primary-text">
            Créez votre compte
          </h2>
        </div>
        <RegisterForm />
        <p className="mt-10 text-center text-sm text-x-secondary-text">
          Déjà un compte ?{' '}
          <a href="/auth_group/login" className="font-medium text-x-accent hover:text-x-accent-hover">
            Connectez-vous
          </a>
        </p>
      </div>
    </div>
  );
}
