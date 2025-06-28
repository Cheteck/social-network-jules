import LoginForm from '@/components/auth/LoginForm';

export default function LoginPage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-gray-900 text-white p-4">
      <div className="w-full max-w-md space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-bold tracking-tight">
            Connectez-vous à votre compte
          </h2>
        </div>
        <LoginForm />
        <p className="mt-10 text-center text-sm text-gray-400">
          Pas encore de compte ?{' '}
          <a href="/register" className="font-medium text-blue-500 hover:text-blue-400">
            Inscrivez-vous
          </a>
        </p>
      </div>
    </div>
  );
}
