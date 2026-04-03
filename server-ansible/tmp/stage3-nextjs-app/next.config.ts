import type { NextConfig } from 'next';

const nextConfig: NextConfig = {
  // Allow images from any hostname (for asset photos)
  images: {
    remotePatterns: [
      { protocol: 'http',  hostname: '**' },
      { protocol: 'https', hostname: '**' },
    ],
  },
};

export default nextConfig;
