import React from 'react';
import { Link } from '@inertiajs/react';
import { 
    Folder as FolderIcon, 
    Lock as LockIcon, 
    Globe as GlobeIcon, 
    Package as PackageIcon, 
    Settings as SettingsIcon, 
    MoreHorizontal as MoreHorizontalIcon, 
    Briefcase as BriefcaseIcon, 
    HardHat as HardHatIcon, 
    Droplets as DropletsIcon, 
    Waves as WavesIcon, 
    School as SchoolIcon, 
    Construction as RoadIcon, 
    GitBranch as BridgeIcon, 
    Trophy as TrophyIcon 
} from 'lucide-react';

const iconMap = {
    Lock: LockIcon,
    Globe: GlobeIcon,
    Package: PackageIcon,
    Settings: SettingsIcon,
    MoreHorizontal: MoreHorizontalIcon,
    Briefcase: BriefcaseIcon,
    HardHat: HardHatIcon,
    Droplets: DropletsIcon,
    Waves: WavesIcon,
    School: SchoolIcon,
    Road: RoadIcon,
    Bridge: BridgeIcon,
    Trophy: TrophyIcon,
};

export default function FolderCard({ folder, onEdit, onDelete }) {
    const Icon = folder.icon && iconMap[folder.icon] ? iconMap[folder.icon] : FolderIcon;
    const summary = folder.contracts_summary || { total: 0, complete: 0, incomplete: 0, percentage: 0 };

    return (
        <Link
            href={route('folders.show', folder.id)}
            className="group relative block"
        >
            <div
                className="relative h-32 rounded-lg shadow-sm border border-gray-200 transition-all hover:shadow-md hover:scale-102 cursor-pointer overflow-hidden"
                style={{ backgroundColor: folder.color || '#EAEAEA' }}
            >
                {/* Icono de carpeta */}
                <div className="absolute top-3 left-3">
                    <Icon className="w-8 h-8 text-gray-700" />
                </div>

                {/* Badge de sistema */}
                {folder.is_system && (
                    <div className="absolute top-3 right-3">
                        <span className="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-600 bg-white/80 rounded">
                            Sistema
                        </span>
                    </div>
                )}

                {/* Nombre de la carpeta */}
                <div className="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/50 to-transparent">
                    <h3 className="text-sm font-semibold text-white truncate">
                        {folder.name}
                    </h3>
                    {folder.description && (
                        <p className="text-xs text-white/80 truncate mt-1">
                            {folder.description}
                        </p>
                    )}
                </div>

                {/* Indicador de progreso */}
                {summary.total > 0 && (
                    <div className="absolute bottom-12 right-3">
                        <div className="flex items-center space-x-1 bg-white/90 rounded-full px-2 py-1">
                            <span className={`text-xs font-medium ${summary.percentage === 100 ? 'text-green-600' : 'text-amber-600'}`}>
                                {summary.complete}/{summary.total}
                            </span>
                        </div>
                    </div>
                )}

                {/* Botones de acción */}
                {!folder.is_system && (
                    <div className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        <div className="flex space-x-1">
                            <button
                                onClick={(e) => {
                                    e.preventDefault();
                                    onEdit(folder);
                                }}
                                className="p-1 bg-white rounded hover:bg-gray-100"
                                title="Editar"
                            >
                                <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button
                                onClick={(e) => {
                                    e.preventDefault();
                                    if (confirm('¿Estás seguro de eliminar esta carpeta?')) {
                                        onDelete(folder);
                                    }
                                }}
                                className="p-1 bg-white rounded hover:bg-red-100"
                                title="Eliminar"
                            >
                                <svg className="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </Link>
    );
}

