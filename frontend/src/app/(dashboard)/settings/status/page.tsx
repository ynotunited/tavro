'use client';

import { useState, useEffect } from 'react';
import api from '@/lib/axios';
import { AxiosError } from 'axios';

interface ProviderConfig {
  provider: string | null;
  page_id?: string;
  component_map?: Record<string, string>;
  is_configured: boolean;
  api_key_masked?: string | null;
}

interface Incident {
  id: number;
  title: string;
  summary: string | null;
  severity: string;
  status: string;
  impacted_components: string[];
  detected_at: string;
  resolved_at: string | null;
  resolved_by: string | null;
  updates: IncidentUpdate[];
}

interface IncidentUpdate {
  id: number;
  status: string;
  message: string;
  created_at: string;
}

interface MaintenanceWindow {
  id: number;
  title: string;
  description: string | null;
  starts_at: string;
  ends_at: string;
  status: string;
  impacted_components: string[];
}

interface SyncLog {
  success: boolean;
  syncable_type: string;
  action: string;
  provider: string;
  error_message: string | null;
  created_at: string;
}

const COMPONENT_LABELS: Record<string, string> = {
  api: 'API',
  web: 'Web App (POS)',
  payments: 'Payments',
  database: 'Database',
  jobs: 'Background Jobs',
  email: 'Email Notifications',
};

const severityStyles: Record<string, string> = {
  minor: 'bg-emerald-100 text-emerald-700',
  major: 'bg-amber-100 text-amber-700',
  critical: 'bg-red-100 text-red-700',
};

const statusStyles: Record<string, string> = {
  investigating: 'bg-red-100 text-red-700',
  identified: 'bg-amber-100 text-amber-700',
  monitoring: 'bg-blue-100 text-blue-700',
  resolved: 'bg-emerald-100 text-emerald-700',
};

export default function StatusPageSettings() {
  const [tab, setTab] = useState<'provider' | 'incidents' | 'maintenance' | 'logs'>('incidents');
  const [config, setConfig] = useState<ProviderConfig | null>(null);
  const [incidents, setIncidents] = useState<Incident[]>([]);
  const [maintenance, setMaintenance] = useState<MaintenanceWindow[]>([]);
  const [logs, setLogs] = useState<SyncLog[]>([]);
  const [loading, setLoading] = useState(true);

  // Form state
  const [providerForm, setProviderForm] = useState({ provider: 'instatus', api_key: '', page_id: '', component_map: '{}' });
  const [incidentForm, setIncidentForm] = useState({ title: '', summary: '', severity: 'major', components: [] as string[] });
  const [maintenanceForm, setMaintenanceForm] = useState({ title: '', description: '', starts_at: '', ends_at: '', components: [] as string[] });
  const [processing, setProcessing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchAll = async () => {
      try {
        const [cfg, inc, maint] = await Promise.all([
          api.get('/status/config'),
          api.get('/status/incidents'),
          api.get('/status/maintenance'),
        ]);
        setConfig(cfg.data.data);
        setIncidents(inc.data.data.data ?? []);
        setMaintenance(maint.data.data.data ?? []);
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    };
    fetchAll();
  }, []);

  // ── Provider ─────────────────────────────────────────────────────────
  const handleSaveConfig = async () => {
    setProcessing(true);
    setError(null);
    try {
      let component_map: Record<string, string> = {};
      try {
        component_map = JSON.parse(providerForm.component_map || '{}');
      } catch {
        throw new Error('component_map must be valid JSON like {"api": "abcdef", "web": "123456"}');
      }

      const res = await api.post('/status/config', {
        provider: providerForm.provider,
        api_key: providerForm.api_key,
        page_id: providerForm.page_id,
        component_map,
      });
      setConfig({
        provider: res.data.data.config.provider,
        page_id: res.data.data.config.page_id,
        component_map: res.data.data.config.component_map,
        is_configured: true,
      });
      alert('Status page connected. Check the connection result above.');
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string }>;
      setError(axiosErr?.response?.data?.message || (err as Error).message);
    } finally {
      setProcessing(false);
    }
  };

  const handleDisconnect = async () => {
    if (!confirm('Disconnect the status page provider?')) return;
    try {
      await api.delete('/status/config');
      setConfig({ provider: null, is_configured: false });
    } catch (err) {
      console.error(err);
    }
  };

  // ── Incidents ─────────────────────────────────────────────────────────
  const handleCreateIncident = async () => {
    setProcessing(true);
    setError(null);
    try {
      await api.post('/status/incidents', {
        title: incidentForm.title,
        summary: incidentForm.summary || undefined,
        severity: incidentForm.severity,
        impacted_components: incidentForm.components,
      });
      setIncidentForm({ title: '', summary: '', severity: 'major', components: [] });
      const res = await api.get('/status/incidents');
      setIncidents(res.data.data.data ?? []);
      alert('Incident reported and subscribers notified.');
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string }>;
      setError(axiosErr?.response?.data?.message || 'Failed to create incident');
    } finally {
      setProcessing(false);
    }
  };

  const handleIncidentAction = async (incident: Incident, status: string, message: string) => {
    setProcessing(true);
    try {
      const action = status === 'resolved' ? 'resolve' : 'update';
      await api.post(`/status/incidents/${incident.id}/${action}`, { status, message });
      const res = await api.get('/status/incidents');
      setIncidents(res.data.data.data ?? []);
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string }>;
      alert(axiosErr?.response?.data?.message || 'Action failed');
    } finally {
      setProcessing(false);
    }
  };

  // ── Maintenance ───────────────────────────────────────────────────────
  const handleCreateMaintenance = async () => {
    setProcessing(true);
    setError(null);
    try {
      await api.post('/status/maintenance', {
        title: maintenanceForm.title,
        description: maintenanceForm.description || undefined,
        starts_at: new Date(maintenanceForm.starts_at).toISOString(),
        ends_at: new Date(maintenanceForm.ends_at).toISOString(),
        impacted_components: maintenanceForm.components,
      });
      setMaintenanceForm({ title: '', description: '', starts_at: '', ends_at: '', components: [] });
      const res = await api.get('/status/maintenance');
      setMaintenance(res.data.data.data ?? []);
      alert('Maintenance window scheduled.');
    } catch (err) {
      const axiosErr = err as AxiosError<{ message?: string }>;
      setError(axiosErr?.response?.data?.message || 'Failed to schedule maintenance');
    } finally {
      setProcessing(false);
    }
  };

  const handleCancelMaintenance = async (id: number) => {
    if (!confirm('Cancel this maintenance window?')) return;
    try {
      await api.post(`/status/maintenance/${id}/cancel`);
      const res = await api.get('/status/maintenance');
      setMaintenance(res.data.data.data ?? []);
    } catch (err) {
      console.error(err);
    }
  };

  // ── Logs ──────────────────────────────────────────────────────────────
  const fetchLogs = async () => {
    try {
      const res = await api.get('/status/sync-logs');
      setLogs(res.data.data.data ?? []);
    } catch (err) {
      console.error(err);
    }
  };

  if (loading) return <div className="p-8 text-center text-gray-500">Loading Status Settings...</div>;

  return (
    <div className="max-w-5xl mx-auto space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-charcoal">Status Page &amp; Incidents</h1>
        <p className="text-sm text-gray-500">Manage the public status page, incidents, and scheduled maintenance.</p>
      </div>

      {/* Provider status banner */}
      <div className={`rounded-xl p-4 flex justify-between items-center border ${config?.is_configured ? 'bg-emerald-50 border-emerald-200' : 'bg-gray-50 border-gray-200'}`}>
        <div className="flex items-center gap-3">
          <span className={`w-3 h-3 rounded-full ${config?.is_configured ? 'bg-emerald-500' : 'bg-gray-400'}`} />
          <div>
            <p className="text-sm font-semibold text-charcoal">
              {config?.is_configured ? `Connected to ${config.provider}` : 'No status page provider configured'}
            </p>
            <p className="text-xs text-gray-500">
              {config?.is_configured
                ? `Page: ${config.page_id} · Key: ${config.api_key_masked}`
                : 'Connect Instatus or BetterStack to publish incidents on an always-available domain.'}
            </p>
          </div>
        </div>
        <button
          onClick={() => setTab('provider')}
          className="px-3 py-1.5 text-sm font-semibold bg-charcoal text-white rounded-lg hover:bg-gray-800"
        >
          Configure
        </button>
      </div>

      {/* Tabs */}
      <div className="flex gap-1 border-b border-gray-200">
        {(['incidents', 'maintenance', 'provider', 'logs'] as const).map((t) => (
          <button
            key={t}
            onClick={() => { setTab(t); if (t === 'logs') fetchLogs(); }}
            className={`px-4 py-2 text-sm font-semibold transition-colors capitalize ${
              tab === t ? 'text-amber-600 border-b-2 border-amber-500' : 'text-gray-500 hover:text-charcoal'
            }`}
          >
            {t}
          </button>
        ))}
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3">{error}</div>
      )}

      {/* ── Incidents tab ────────────────────────────────────────────── */}
      {tab === 'incidents' && (
        <div className="space-y-6">
          {/* Create incident */}
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-lg font-bold text-charcoal mb-4">Report an Incident</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <input
                value={incidentForm.title}
                onChange={(e) => setIncidentForm({ ...incidentForm, title: e.target.value })}
                placeholder="Incident title (e.g. API is down)"
                className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-500"
              />
              <select
                value={incidentForm.severity}
                onChange={(e) => setIncidentForm({ ...incidentForm, severity: e.target.value })}
                className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"
              >
                <option value="minor">Minor</option>
                <option value="major">Major</option>
                <option value="critical">Critical</option>
              </select>
            </div>
            <textarea
              value={incidentForm.summary}
              onChange={(e) => setIncidentForm({ ...incidentForm, summary: e.target.value })}
              placeholder="Summary — what are we investigating?"
              rows={3}
              className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm mb-4 focus:outline-none"
            />
            <div className="mb-4">
              <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Impacted Components</p>
              <div className="flex flex-wrap gap-2">
                {Object.entries(COMPONENT_LABELS).map(([key, label]) => (
                  <button
                    key={key}
                    onClick={() => setIncidentForm({
                      ...incidentForm,
                      components: incidentForm.components.includes(key)
                        ? incidentForm.components.filter((c) => c !== key)
                        : [...incidentForm.components, key],
                    })}
                    className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors ${
                      incidentForm.components.includes(key)
                        ? 'bg-charcoal text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    }`}
                  >
                    {label}
                  </button>
                ))}
              </div>
            </div>
            <button
              onClick={handleCreateIncident}
              disabled={processing || !incidentForm.title}
              className="px-4 py-2 text-sm font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Report Incident &amp; Notify Subscribers
            </button>
          </div>

          {/* Incident list */}
          <div className="space-y-4">
            {incidents.length === 0 && (
              <div className="text-center text-gray-400 text-sm py-10 bg-white rounded-xl border border-gray-100">
                No incidents reported. All systems operational.
              </div>
            )}
            {incidents.map((incident) => (
              <div key={incident.id} className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div className="flex justify-between items-start">
                  <div>
                    <div className="flex items-center gap-2">
                      <h4 className="font-bold text-charcoal">{incident.title}</h4>
                      <span className={`px-2 py-0.5 rounded-full text-xs font-bold uppercase ${severityStyles[incident.severity]}`}>
                        {incident.severity}
                      </span>
                      <span className={`px-2 py-0.5 rounded-full text-xs font-bold uppercase ${statusStyles[incident.status]}`}>
                        {incident.status}
                      </span>
                    </div>
                    <p className="text-sm text-gray-500 mt-1">
                      Detected: {new Date(incident.detected_at).toLocaleString()}
                      {incident.resolved_at && <span> · Resolved: {new Date(incident.resolved_at).toLocaleString()}</span>}
                    </p>
                    {incident.impacted_components?.length > 0 && (
                      <div className="flex flex-wrap gap-1 mt-2">
                        {incident.impacted_components.map((c) => (
                          <span key={c} className="text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-600">
                            {COMPONENT_LABELS[c] ?? c}
                          </span>
                        ))}
                      </div>
                    )}
                  </div>

                  {incident.status !== 'resolved' && (
                    <div className="flex gap-2">
                      <select
                        onChange={(e) => {
                          if (e.target.value) {
                            const msg = prompt(`Message for "${e.target.value}" status:`, `We're ${e.target.value === 'monitoring' ? 'now monitoring the situation' : 'working to resolve'}.`);
                            if (msg) handleIncidentAction(incident, e.target.value, msg);
                          }
                        }}
                        value=""
                        className="text-xs border border-gray-200 rounded-lg px-2 py-1"
                      >
                        <option value="">Update →</option>
                        <option value="identified">Identified</option>
                        <option value="monitoring">Monitoring</option>
                      </select>
                      <button
                        onClick={() => {
                          const msg = prompt('Resolution message:', 'This incident has been resolved. All systems are operational.');
                          if (msg) handleIncidentAction(incident, 'resolved', msg);
                        }}
                        className="px-3 py-1 text-xs font-semibold bg-emerald-600 text-white rounded-lg hover:bg-emerald-700"
                      >
                        Resolve
                      </button>
                    </div>
                  )}
                </div>

                {/* Timeline */}
                {incident.updates?.length > 0 && (
                  <div className="mt-4 space-y-2 border-l-2 border-gray-200 pl-4">
                    {incident.updates.map((update) => (
                      <div key={update.id} className="text-sm">
                        <p className="text-xs font-semibold uppercase text-gray-400">
                          {update.status} · {new Date(update.created_at).toLocaleString()}
                        </p>
                        <p className="text-gray-700">{update.message}</p>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ── Maintenance tab ──────────────────────────────────────────── */}
      {tab === 'maintenance' && (
        <div className="space-y-6">
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 className="text-lg font-bold text-charcoal mb-4">Schedule Maintenance Window</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <input
                value={maintenanceForm.title}
                onChange={(e) => setMaintenanceForm({ ...maintenanceForm, title: e.target.value })}
                placeholder="Maintenance title (e.g. Database upgrade)"
                className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"
              />
              <input
                type="datetime-local"
                value={maintenanceForm.starts_at}
                onChange={(e) => setMaintenanceForm({ ...maintenanceForm, starts_at: e.target.value })}
                className="border border-gray-200 rounded-lg px-3 py-2 text-sm"
              />
              <textarea
                value={maintenanceForm.description}
                onChange={(e) => setMaintenanceForm({ ...maintenanceForm, description: e.target.value })}
                placeholder="What will happen during the maintenance?"
                rows={2}
                className="col-span-2 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none"
              />
              <input
                type="datetime-local"
                value={maintenanceForm.ends_at}
                onChange={(e) => setMaintenanceForm({ ...maintenanceForm, ends_at: e.target.value })}
                className="border border-gray-200 rounded-lg px-3 py-2 text-sm"
              />
            </div>
            <div className="mb-4">
              <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Impacted Components</p>
              <div className="flex flex-wrap gap-2">
                {Object.entries(COMPONENT_LABELS).map(([key, label]) => (
                  <button
                    key={key}
                    onClick={() => setMaintenanceForm({
                      ...maintenanceForm,
                      components: maintenanceForm.components.includes(key)
                        ? maintenanceForm.components.filter((c) => c !== key)
                        : [...maintenanceForm.components, key],
                    })}
                    className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors ${
                      maintenanceForm.components.includes(key)
                        ? 'bg-charcoal text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    }`}
                  >
                    {label}
                  </button>
                ))}
              </div>
            </div>
            <button
              onClick={handleCreateMaintenance}
              disabled={processing || !maintenanceForm.title || !maintenanceForm.starts_at || !maintenanceForm.ends_at}
              className="px-4 py-2 text-sm font-semibold bg-charcoal text-white rounded-lg hover:bg-gray-800 disabled:opacity-50"
            >
              Schedule Maintenance
            </button>
          </div>

          <div className="space-y-4">
            {maintenance.map((window) => (
              <div key={window.id} className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex justify-between items-start">
                <div>
                  <div className="flex items-center gap-2">
                    <h4 className="font-bold text-charcoal">{window.title}</h4>
                    <span className={`px-2 py-0.5 rounded-full text-xs font-bold uppercase ${
                      window.status === 'completed' || window.status === 'cancelled'
                        ? 'bg-gray-100 text-gray-500'
                        : window.status === 'in_progress'
                          ? 'bg-blue-100 text-blue-700'
                          : 'bg-amber-100 text-amber-700'
                    }`}>
                      {window.status}
                    </span>
                  </div>
                  <p className="text-sm text-gray-500 mt-1">
                    {new Date(window.starts_at).toLocaleString()} → {new Date(window.ends_at).toLocaleString()}
                  </p>
                  {window.description && <p className="text-sm text-gray-600 mt-2">{window.description}</p>}
                  {window.impacted_components?.length > 0 && (
                    <div className="flex flex-wrap gap-1 mt-2">
                      {window.impacted_components.map((c) => (
                        <span key={c} className="text-xs bg-gray-100 px-2 py-0.5 rounded text-gray-600">
                          {COMPONENT_LABELS[c] ?? c}
                        </span>
                      ))}
                    </div>
                  )}
                </div>
                {window.status !== 'cancelled' && window.status !== 'completed' && (
                  <button
                    onClick={() => handleCancelMaintenance(window.id)}
                    className="px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg"
                  >
                    Cancel
                  </button>
                )}
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ── Provider tab ─────────────────────────────────────────────── */}
      {tab === 'provider' && (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-xl">
          <h3 className="text-lg font-bold text-charcoal mb-4">Connect Status Page Provider</h3>
          <p className="text-sm text-gray-500 mb-4">
            The status page runs on the provider&apos;s infrastructure, so it stays online even when Tavro is down.
            Use a free <strong>Instatus</strong> or <strong>BetterStack</strong> account.
          </p>

          <div className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Provider</label>
              <select
                value={providerForm.provider}
                onChange={(e) => setProviderForm({ ...providerForm, provider: e.target.value })}
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
              >
                <option value="instatus">Instatus (free: 15 monitors, 200 subscribers)</option>
                <option value="betterstack">BetterStack (free: 10 monitors, 1000 subscribers)</option>
              </select>
            </div>

            <div>
              <label className="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">API Key</label>
              <input
                type="password"
                value={providerForm.api_key}
                onChange={(e) => setProviderForm({ ...providerForm, api_key: e.target.value })}
                placeholder="Secret key from your provider dashboard"
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
              />
            </div>

            {providerForm.provider === 'instatus' && (
              <div>
                <label className="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Status Page ID</label>
                <input
                  value={providerForm.page_id}
                  onChange={(e) => setProviderForm({ ...providerForm, page_id: e.target.value })}
                  placeholder="Your Instatus page UUID"
                  className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                />
              </div>
            )}

            <div>
              <label className="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
                Component Map (JSON)
              </label>
              <input
                value={providerForm.component_map}
                onChange={(e) => setProviderForm({ ...providerForm, component_map: e.target.value })}
                placeholder='{"api": "comp_id_1", "web": "comp_id_2", "payments": "comp_id_3"}'
                className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono text-xs"
              />
              <p className="text-xs text-gray-400 mt-1">
                Map Tavro components to provider component IDs so incidents show the correct red/green status.
                Get IDs from your provider&apos;s status page settings.
              </p>
            </div>

            {config?.is_configured && (
              <button
                onClick={handleDisconnect}
                className="px-4 py-2 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg"
              >
                Disconnect Provider
              </button>
            )}

            <button
              onClick={handleSaveConfig}
              disabled={processing || !providerForm.api_key}
              className="w-full px-4 py-2 text-sm font-semibold bg-charcoal text-white rounded-lg hover:bg-gray-800 disabled:opacity-50"
            >
              {processing ? 'Testing Connection...' : config?.is_configured ? 'Reconnect / Update' : 'Connect & Test'}
            </button>
          </div>
        </div>
      )}

      {/* ── Logs tab ─────────────────────────────────────────────────── */}
      {tab === 'logs' && (
        <div className="space-y-4">
          {logs.length === 0 && (
            <div className="text-center text-gray-400 text-sm py-10 bg-white rounded-xl border border-gray-100">
              No sync activity yet.
            </div>
          )}
          {logs.map((log, i) => (
            <div key={i} className={`bg-white rounded-xl shadow-sm border p-4 flex justify-between items-start ${log.success ? 'border-gray-100' : 'border-red-200'}`}>
              <div>
                <div className="flex items-center gap-2">
                  <span className={`w-2 h-2 rounded-full ${log.success ? 'bg-emerald-500' : 'bg-red-500'}`} />
                  <p className="text-sm font-semibold text-charcoal capitalize">
                    {log.syncable_type.replace(/^App\\Models\\/, '')} · {log.action}
                  </p>
                  <span className="text-xs text-gray-400">{log.provider}</span>
                </div>
                {log.error_message && <p className="text-xs text-red-600 mt-1">{log.error_message}</p>}
              </div>
              <span className="text-xs text-gray-400 whitespace-nowrap">
                {new Date(log.created_at).toLocaleString()}
              </span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}