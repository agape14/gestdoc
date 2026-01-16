import React from 'react';
import { Link } from '@inertiajs/react';
import { HomeIcon, ChevronRightIcon } from 'lucide-react';

export default function FolderBreadcrumb({ path = [] }) {
    return (
        <nav className="flex items-center space-x-2 text-sm text-gray-600 mb-6">
            <Link
                href={route('folders.index')}
                className="flex items-center hover:text-gray-900 transition-colors"
            >
                <HomeIcon className="w-4 h-4" />
                <span className="ml-1">Inicio</span>
            </Link>

            {path.map((folder, index) => (
                <React.Fragment key={folder.id}>
                    <ChevronRightIcon className="w-4 h-4 text-gray-400" />
                    {index === path.length - 1 ? (
                        <span className="font-medium text-gray-900">
                            {folder.name}
                        </span>
                    ) : (
                        <Link
                            href={route('folders.show', folder.id)}
                            className="hover:text-gray-900 transition-colors"
                        >
                            {folder.name}
                        </Link>
                    )}
                </React.Fragment>
            ))}
        </nav>
    );
}

