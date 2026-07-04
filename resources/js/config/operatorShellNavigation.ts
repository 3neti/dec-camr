import {
    Activity,
    Building2,
    FileBarChart,
    FileText,
    GaugeCircle,
    LayoutGrid,
    Power,
    Settings2,
    UserCog,
    Users,
} from '@lucide/vue';
import type { NavItem } from '@/types';

export type OperatorShellNavItem = NavItem & {
    adminOnly?: boolean;
    quickAccess?: boolean;
};

export type OperatorShellNavSection = {
    title: string;
    items: OperatorShellNavItem[];
};

const sections: OperatorShellNavSection[] = [
    {
        title: 'Core',
        items: [
            {
                title: 'Dashboard',
                href: '/site',
                icon: LayoutGrid,
                quickAccess: true,
            },
        ],
    },
    {
        title: 'Maintenance',
        items: [
            {
                title: 'Company',
                href: '/company',
                icon: Building2,
                quickAccess: false,
            },
            {
                title: 'Division',
                href: '/division',
                icon: Building2,
                quickAccess: false,
            },
            {
                title: 'Site',
                href: '/site',
                icon: GaugeCircle,
                quickAccess: true,
            },
            {
                title: 'Building',
                href: '/building',
                icon: Activity,
                quickAccess: false,
            },
            {
                title: 'Gateway',
                href: '/gateway',
                icon: Power,
                quickAccess: true,
            },
            {
                title: 'Meter',
                href: '/meter',
                icon: FileText,
                quickAccess: true,
            },
            {
                title: 'Configuration',
                href: '/configuration_file',
                icon: Settings2,
                quickAccess: false,
            },
        ],
    },
    {
        title: 'Reports',
        items: [
            {
                title: 'Analytics',
                href: '/analytics',
                icon: FileBarChart,
                quickAccess: true,
            },
            {
                title: 'SAP Report',
                href: '/sap_report',
                icon: FileBarChart,
                quickAccess: false,
            },
            {
                title: 'Raw Report',
                href: '/raw_report',
                icon: FileBarChart,
                quickAccess: true,
            },
            {
                title: 'Site/Building',
                href: '/site_report',
                icon: FileBarChart,
                quickAccess: false,
            },
            {
                title: 'Consumption',
                href: '/consumption_report',
                icon: FileBarChart,
                quickAccess: false,
            },
            {
                title: 'Demand',
                href: '/demand_report',
                icon: FileBarChart,
                quickAccess: false,
            },
        ],
    },
    {
        title: 'Administration',
        items: [
            {
                title: 'Users',
                href: '/user',
                icon: Users,
                adminOnly: true,
                quickAccess: true,
            },
            {
                title: 'User Access',
                href: '/user_site_access',
                icon: UserCog,
                adminOnly: true,
                quickAccess: false,
            },
        ],
    },
];

function isAdminUser(userType: string | null | undefined): boolean {
    return (userType ?? '').toLowerCase() === 'admin';
}

export function getOperatorShellNavigationSections(userType: string | null | undefined): OperatorShellNavSection[] {
    const isAdmin = isAdminUser(userType);

    return sections
        .map((section) => ({
            ...section,
            items: section.items.filter((item) => item.adminOnly !== true || isAdmin),
        }))
        .filter((section) => section.items.length > 0);
}

export function getOperatorShellQuickAccessItems(userType: string | null | undefined): NavItem[] {
    return getOperatorShellNavigationSections(userType)
        .flatMap((section) => section.items)
        .filter((item) => item.quickAccess) as NavItem[];
}
