import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Cookie Policy | Tavro",
  description: "Tavro's Cookie Policy — how we use cookies and similar technologies.",
};

export default function CookiesPage() {
  return (
    <article className="prose-charcoal">
      <h1 className="text-4xl font-bold font-display text-charcoal-900 mb-2">Cookie Policy</h1>
      <p className="text-sm text-charcoal-500 mb-8">Last updated: August 26, 2026</p>

      <div className="space-y-8 text-charcoal-700 leading-relaxed">
        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">1. What Are Cookies</h2>
          <p>
            Cookies are small text files stored on your device when you visit a website. They help websites remember your preferences, keep you logged in, and understand how you use the service. We also use similar technologies such as local storage, session storage, and service workers (for offline functionality).
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">2. How We Use Cookies</h2>
          <p>We use cookies and similar technologies for the following purposes:</p>

          <div className="overflow-x-auto">
            <table className="w-full text-sm border border-charcoal-200 rounded-lg overflow-hidden mt-4">
              <thead className="bg-charcoal-50 text-charcoal-800">
                <tr>
                  <th className="px-4 py-3 text-left font-semibold">Type</th>
                  <th className="px-4 py-3 text-left font-semibold">Purpose</th>
                  <th className="px-4 py-3 text-left font-semibold">Duration</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-charcoal-200">
                <tr>
                  <td className="px-4 py-3 font-medium">Authentication</td>
                  <td className="px-4 py-3">Keep you logged in and maintain your session.</td>
                  <td className="px-4 py-3">Session / 24 hours</td>
                </tr>
                <tr>
                  <td className="px-4 py-3 font-medium">Security</td>
                  <td className="px-4 py-3">Protect against CSRF attacks and verify request origin.</td>
                  <td className="px-4 py-3">Session</td>
                </tr>
                <tr>
                  <td className="px-4 py-3 font-medium">Preferences</td>
                  <td className="px-4 py-3">Remember your settings (branch selection, display preferences).</td>
                  <td className="px-4 py-3">Persistent (up to 1 year)</td>
                </tr>
                <tr>
                  <td className="px-4 py-3 font-medium">Offline Storage</td>
                  <td className="px-4 py-3">Store transaction data locally for offline POS operation using IndexedDB.</td>
                  <td className="px-4 py-3">Until cleared</td>
                </tr>
                <tr>
                  <td className="px-4 py-3 font-medium">Analytics</td>
                  <td className="px-4 py-3">Understand usage patterns to improve the Service.</td>
                  <td className="px-4 py-3">Up to 2 years</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">3. Specific Cookies</h2>

          <h3 className="text-lg font-semibold text-charcoal-800 mt-6 mb-2">3.1 Essential Cookies</h3>
          <p>These are required for the Service to function and cannot be disabled:</p>
          <ul className="list-disc pl-6 space-y-2">
            <li><strong>session_id:</strong> Server-side session token for authentication.</li>
            <li><strong>csrf_token:</strong> Cross-site request forgery protection token.</li>
            <li><strong>tavro_offline_db:</strong> IndexedDB database for offline transaction storage.</li>
          </ul>

          <h3 className="text-lg font-semibold text-charcoal-800 mt-6 mb-2">3.2 Functional Cookies</h3>
          <p>These enhance your experience but are not strictly required:</p>
          <ul className="list-disc pl-6 space-y-2">
            <li><strong>branch_id:</strong> Remembers your selected branch across sessions.</li>
            <li><strong>sidebar_state:</strong> Remembers whether the sidebar is collapsed or expanded.</li>
            <li><strong>theme_preference:</strong> Stores your light/dark mode preference.</li>
          </ul>

          <h3 className="text-lg font-semibold text-charcoal-800 mt-6 mb-2">3.3 Analytics Cookies</h3>
          <p>If enabled by your organisation administrator:</p>
          <ul className="list-disc pl-6 space-y-2">
            <li><strong>_tavro_session:</strong> Anonymous usage analytics for feature improvement.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">4. Local Storage &amp; IndexedDB</h2>
          <p>
            In addition to cookies, Tavro uses browser local storage and IndexedDB for:
          </p>
          <ul className="list-disc pl-6 space-y-2">
            <li><strong>Offline Mode:</strong> Pending orders, cart data, and transaction records are stored in IndexedDB when the network is unavailable. This data is automatically synced to our servers when connectivity is restored and then purged from local storage.</li>
            <li><strong>Auth Tokens:</strong> Authentication tokens may be stored in local storage for session persistence.</li>
            <li><strong>UI State:</strong> Dashboard layout preferences, recently viewed items, and draft data.</li>
          </ul>
          <p className="mt-3">
            You can clear local storage and IndexedDB at any time through your browser settings, though this will reset your offline data and preferences.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">5. Third-Party Cookies</h2>
          <p>
            Tavro integrates with third-party services that may set their own cookies:
          </p>
          <ul className="list-disc pl-6 space-y-2">
            <li><strong>Paystack / Flutterwave:</strong> Payment processing iframes may set session cookies during payment flows.</li>
            <li><strong>Sentry:</strong> Error tracking may set cookies for session deduplication.</li>
          </ul>
          <p className="mt-3">
            These third-party cookies are governed by their respective privacy policies. We do not control third-party cookies and recommend reviewing their policies directly.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">6. Managing Cookies</h2>
          <p>You can control cookies through your browser settings:</p>
          <ul className="list-disc pl-6 space-y-2">
            <li><strong>Chrome:</strong> Settings &gt; Privacy and Security &gt; Cookies and other site data.</li>
            <li><strong>Firefox:</strong> Settings &gt; Privacy &amp; Security &gt; Cookies and Site Data.</li>
            <li><strong>Safari:</strong> Preferences &gt; Privacy &gt; Manage Website Data.</li>
            <li><strong>Edge:</strong> Settings &gt; Privacy, Search, and Services &gt; Cookies.</li>
          </ul>
          <p className="mt-3">
            Disabling essential cookies will prevent you from logging in and using the Service. Disabling functional cookies may reset your preferences on each visit.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">7. Do Not Track</h2>
          <p>
            Some browsers offer a &ldquo;Do Not Track&rdquo; (DNT) signal. There is currently no industry standard for how to respond to DNT signals. Tavro processes analytics data in aggregate and does not track individual users across websites for advertising purposes.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">8. Changes to This Policy</h2>
          <p>
            We may update this Cookie Policy from time to time. Changes will be posted on this page with an updated &ldquo;Last updated&rdquo; date. Significant changes will be communicated via email or in-app notification.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">9. Contact</h2>
          <ul className="list-disc pl-6 space-y-1">
            <li>Privacy: <a href="mailto:privacy@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">privacy@tavro.ng</a></li>
            <li>Data Protection Officer: <a href="mailto:dpo@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">dpo@tavro.ng</a></li>
          </ul>
        </section>
      </div>
    </article>
  );
}
