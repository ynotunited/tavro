import Link from 'next/link';
import { Card, CardContent } from '@/components/ui/Card';

const settingsSections = [
  {
    label: 'Team',
    description: 'Manage staff, roles, and invitations.',
    href: '/settings/team',
    icon: '👥',
  },
  {
    label: 'Branches',
    description: 'Add and manage business locations.',
    href: '/settings/branches',
    icon: '🏢',
  },
  {
    label: 'Billing',
    description: 'Subscriptions, plans, and payment details.',
    href: '/settings/billing',
    icon: '💳',
  },
  {
    label: 'Status Page',
    description: 'Incidents and service availability.',
    href: '/settings/status',
    icon: '🟢',
  },
  {
    label: 'Audit Logs',
    description: 'Review team activity and security events.',
    href: '/settings/audit',
    icon: '🔐',
  },
  {
    label: 'Notifications',
    description: 'Configure alert and notification preferences.',
    href: '/settings/notifications',
    icon: '🔔',
  },
];

export default function SettingsIndexPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-charcoal">Settings</h1>
        <p className="text-sm text-gray-500">Manage your organization, billing, and security.</p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {settingsSections.map((section) => (
          <Link key={section.href} href={section.href} className="group">
            <Card className="h-full transition-shadow hover:shadow-md">
              <CardContent className="flex items-start gap-4">
                <span className="text-2xl leading-none">{section.icon}</span>
                <div>
                  <p className="font-semibold text-gray-900 group-hover:text-amber transition-colors">
                    {section.label}
                  </p>
                  <p className="text-sm text-gray-500 mt-1">{section.description}</p>
                </div>
              </CardContent>
            </Card>
          </Link>
        ))}
      </div>
    </div>
  );
}
