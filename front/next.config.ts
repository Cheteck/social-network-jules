import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  async rewrites() {
    return [
      {
        source: '/sanctum/:path*',
        destination: 'http://localhost:8000/sanctum/:path*', // port backend Laravel
      },
      {
        source: '/register',
        destination: 'http://localhost:8000/register',
      },
      {
        source: '/login',
        destination: 'http://localhost:8000/login',
      },
      // ... idem pour /logout, /api/*
    ];
  },
};

export default nextConfig;
