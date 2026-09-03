import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Data & Compliance | Tavro",
  description: "Tavro's data protection and regulatory compliance commitments.",
};

export default function CompliancePage() {
  return (
    <article className="prose-charcoal">
      <h1 className="text-4xl font-bold font-display text-charcoal-900 mb-2">Data &amp; Compliance</h1>
      <p className="text-sm text-charcoal-500 mb-8">Last updated: August 26, 2026</p>

      <div className="space-y-8 text-charcoal-700 leading-relaxed">
        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">1. Our Commitment</h2>
          <p>
            Tavro is built on a foundation of trust, security, and regulatory compliance. We handle restaurant operational data — including financial records, employee information, and customer transactions — with the highest standards of care. This page details our compliance posture across applicable regulatory frameworks.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">2. Nigeria Data Protection Regulation (NDPR)</h2>
          <p>
            Tavro fully complies with the Nigeria Data Protection Regulation (NDPR) 2019 and the Nigeria Data Protection Act (NDPA) 2023, as enforced by the Nigeria Data Protection Commission (NDPC).
          </p>
          <ul className="list-disc pl-6 space-y-2 mt-3">
            <li><strong>Lawful Processing:</strong> We process personal data only on a lawful basis — consent, contractual necessity, legitimate interest, or legal obligation.</li>
            <li><strong>Data Minimisation:</strong> We collect only the data strictly necessary to provide and improve the Service.</li>
            <li><strong>Purpose Limitation:</strong> Data is used only for the purposes for which it was collected.</li>
            <li><strong>Accuracy:</strong> We provide tools for users to keep their data accurate and up to date.</li>
            <li><strong>Storage Limitation:</strong> Data is retained only as long as necessary for the stated purpose or as required by law.</li>
            <li><strong>Data Protection Impact Assessment (DPIA):</strong> We conduct DPIAs for high-risk processing activities.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">3. Data Protection Officer</h2>
          <p>
            We have appointed a Data Protection Officer (DPO) as required by the NDPA. Our DPO oversees compliance, advises on data impact assessments, and serves as the point of contact for data subjects and the NDPC.
          </p>
          <ul className="list-disc pl-6 space-y-1 mt-3">
            <li>DPO Email: <a href="mailto:dpo@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">dpo@tavro.ng</a></li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">4. PCI DSS Compliance</h2>
          <p>
            Tavro does not directly process, store, or transmit cardholder data. All payment processing is handled by our PCI DSS Level 1 certified partners (Paystack and Flutterwave). Our integration is designed to ensure that sensitive card data never touches our servers.
          </p>
          <ul className="list-disc pl-6 space-y-2 mt-3">
            <li>We use tokenised payment references instead of raw card numbers.</li>
            <li>Payment webhooks are verified with HMAC signatures to prevent tampering.</li>
            <li>We maintain audit logs of all payment-related transactions.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">5. Financial Record Compliance</h2>
          <p>
            As a restaurant management system handling financial transactions, Tavro maintains:
          </p>
          <ul className="list-disc pl-6 space-y-2 mt-3">
            <li><strong>Immutable Audit Trails:</strong> Every payment state change is recorded in an append-only payment ledger with full traceability.</li>
            <li><strong>Tax Compliance:</strong> Support for VAT calculations aligned with the Federal Inland Revenue Service (FIRS) requirements.</li>
            <li><strong>Transaction Integrity:</strong> Idempotency keys and deduplication prevent duplicate charges and double-processing.</li>
            <li><strong>Record Retention:</strong> Financial records are retained for a minimum of 6 years as required by the Companies and Allied Matters Act (CAMA) and Nigerian tax law.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">6. Information Security Management</h2>
          <p>Tavro implements an Information Security Management System (ISMS) covering:</p>
          <ul className="list-disc pl-6 space-y-2 mt-3">
            <li><strong>Encryption:</strong> TLS 1.3 for data in transit, AES-256 for data at rest.</li>
            <li><strong>Access Control:</strong> Role-based access control (RBAC) with least-privilege principles. All admin actions require authentication.</li>
            <li><strong>Network Security:</strong> Web Application Firewall (WAF), DDoS protection, and intrusion detection.</li>
            <li><strong>Vulnerability Management:</strong> Regular automated and manual security testing, including dependency audits.</li>
            <li><strong>Incident Response:</strong> Documented incident response plan with defined escalation procedures and user notification within 72 hours of confirmed breaches.</li>
            <li><strong>Business Continuity:</strong> Automated database backups, multi-region redundancy, and disaster recovery procedures.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">7. Employee &amp; Staff Data</h2>
          <p>
            When restaurant owners add staff members to Tavro, the following data may be processed:
          </p>
          <ul className="list-disc pl-6 space-y-2 mt-3">
            <li>Name, role, and contact information for account provisioning.</li>
            <li>Shift records, clock-in/out times, and performance data for operational reporting.</li>
            <li>Access logs for security and audit purposes.</li>
          </ul>
          <p className="mt-3">
            The restaurant owner (data controller) is responsible for obtaining necessary consent from staff and informing them of data processing. Tavro acts as a data processor on behalf of the restaurant owner.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">8. Cross-Border Data Transfers</h2>
          <p>
            Our infrastructure is hosted on cloud providers with data centres in multiple regions. Where personal data is transferred outside Nigeria, we ensure appropriate safeguards are in place in compliance with the NDPA, including:
          </p>
          <ul className="list-disc pl-6 space-y-2 mt-3">
            <li>Standard contractual clauses with data processors.</li>
            <li>Adequacy assessments of recipient jurisdictions.</li>
            <li>Encryption of data in transit and at rest during transfer.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">9. Third-Party Compliance</h2>
          <p>Our key third-party processors and their compliance certifications:</p>
          <ul className="list-disc pl-6 space-y-2 mt-3">
            <li><strong>Paystack:</strong> PCI DSS Level 1 certified, licensed by the Central Bank of Nigeria (CBN).</li>
            <li><strong>Flutterwave:</strong> PCI DSS Level 1 certified, licensed by the CBN.</li>
            <li><strong>Infrastructure (AWS/GCP):</strong> SOC 2 Type II, ISO 27001, PCI DSS compliant.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">10. Compliance Certifications &amp; Audits</h2>
          <p>
            Tavro undergoes regular third-party security assessments and is committed to pursuing relevant certifications as we scale. Current and planned compliance initiatives include:
          </p>
          <ul className="list-disc pl-6 space-y-2 mt-3">
            <li>Annual penetration testing by independent security firms.</li>
            <li>Quarterly vulnerability scans and dependency audits.</li>
            <li>Internal ISMS audits on a semi-annual basis.</li>
            <li>Nigeria Data Protection Compliance Organisation (NDPC) registration and annual audit filing.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">11. Contact</h2>
          <ul className="list-disc pl-6 space-y-1">
            <li>Data Protection Officer: <a href="mailto:dpo@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">dpo@tavro.ng</a></li>
            <li>Security Team: <a href="mailto:security@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">security@tavro.ng</a></li>
            <li>General: <a href="mailto:hello@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">hello@tavro.ng</a></li>
          </ul>
        </section>
      </div>
    </article>
  );
}
