import React from 'react';
import NewPostForm from '@/components/NewPostForm';
import Timeline from '@/components/Timeline';

export default function FeedPage() {
  return (
    <div className="w-full">
      <NewPostForm />
      <Timeline />
    </div>
  );
}
