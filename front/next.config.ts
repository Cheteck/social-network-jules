import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // The rewrites configuration is removed as the Axios client (apiClient)
  // now uses a baseURL to directly target the Laravel backend (http://127.0.0.1:8000).
  // This makes the Next.js proxy unnecessary for these API calls.
  //
  // async rewrites() {
  //   return [
  //     {
  //       source: '/sanctum/:path*',
  //       destination: 'http://localhost:8000/sanctum/:path*', // port backend Laravel
  //     },
  //     {
  //       source: '/register',
  //       destination: 'http://localhost:8000/register',
  //     },
  //     {
  //       source: '/login',
  //       destination: 'http://localhost:8000/login',
  //     },
  //     // ... idem pour /logout, /api/*
  //   ];
  // },
};

export default nextConfig;
