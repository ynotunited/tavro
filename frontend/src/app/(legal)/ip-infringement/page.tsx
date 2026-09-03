import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "IP Infringement Policy | Tavro",
  description: "Tavro's Intellectual Property Infringement policy and DMCA-like reporting process.",
};

export default function IPInfringementPage() {
  return (
    <article className="prose-charcoal">
      <h1 className="text-4xl font-bold font-display text-charcoal-900 mb-2">IP Infringement Policy</h1>
      <p className="text-sm text-charcoal-500 mb-8">Last updated: August 26, 2026</p>

      <div className="space-y-8 text-charcoal-700 leading-relaxed">
        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">1. Overview</h2>
          <p>
            Tavro Technologies respects the intellectual property rights of others and expects our users to do the same. This policy describes how to report alleged intellectual property infringement on the Tavro platform, and how Tavro handles such reports.
          </p>
          <p>
            This policy applies to all content and materials uploaded, displayed, or transmitted through the Service, including menu items, images, logos, text, and any other user-generated content.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">2. Tavro&rsquo;s Intellectual Property</h2>
          <p>
            All Tavro branding, logos, software, design, documentation, and proprietary technology are owned by Tavro Technologies and protected under Nigerian intellectual property law, including the Trademarks Act (Cap T13, LFN 2004), the Copyright Act (Cap C28, LFN 2004), and applicable international treaties.
          </p>
          <p>Unauthorised use of Tavro&rsquo;s intellectual property includes but is not limited to:</p>
          <ul className="list-disc pl-6 space-y-2">
            <li>Using Tavro&rsquo;s name, logo, or branding in a way that implies endorsement or affiliation without written permission.</li>
            <li>Copying, modifying, or distributing Tavro&rsquo;s software or documentation.</li>
            <li>Creating derivative works based on Tavro&rsquo;s proprietary code, design, or content.</li>
            <li>Scraping, reproducing, or republishing Tavro&rsquo;s website content or marketing materials.</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">3. Reporting IP Infringement</h2>
          <p>
            If you believe that your intellectual property rights have been infringed on the Tavro platform, you may submit a written notice to our designated IP agent. Your notice must include:
          </p>

          <div className="bg-charcoal-50 border border-charcoal-200 rounded-xl p-6 my-4">
            <h3 className="font-semibold text-charcoal-800 mb-3">Required Information</h3>
            <ol className="list-decimal pl-6 space-y-2">
              <li><strong>Identification of the IP right:</strong> Description of the copyrighted work, trademark, patent, or other IP right claimed to be infringed.</li>
              <li><strong>Identification of the infringing material:</strong> The specific content or URL on the Tavro platform that you believe infringes your IP right.</li>
              <li><strong>Proof of ownership:</strong> Evidence that you own or control the IP right (registration certificates, trademark filings, copyright registration, or other documentation).</li>
              <li><strong>Contact information:</strong> Your full legal name, address, telephone number, and email address.</li>
              <li><strong>Good faith statement:</strong> A statement that you have a good faith belief that the use of the material is not authorised by the IP owner, its agent, or the law.</li>
              <li><strong>Accuracy statement:</strong> A statement that the information in your notice is accurate, and under penalty of perjury, that you are the IP owner or authorised to act on behalf of the owner.</li>
              <li><strong>Signature:</strong> Physical or electronic signature of the IP owner or authorised representative.</li>
            </ol>
          </div>

          <p>Submit IP infringement notices to:</p>
          <ul className="list-disc pl-6 space-y-1 mt-3">
            <li>Email: <a href="mailto:ip@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">ip@tavro.ng</a></li>
            <li>Subject line: &ldquo;IP Infringement Notice — [Your Name/Company]&rdquo;</li>
          </ul>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">4. Our Response Process</h2>
          <ol className="list-decimal pl-6 space-y-3">
            <li><strong>Acknowledgement:</strong> We will acknowledge receipt of your notice within 2 business days.</li>
            <li><strong>Review:</strong> Our team will review the claim and may contact you for additional information.</li>
            <li><strong>Action:</strong> If the claim is valid, we will remove or disable access to the infringing material and notify the user who posted it.</li>
            <li><strong>Counter-Notice:</strong> The accused user may submit a counter-notice if they believe the material was removed in error. We will forward valid counter-notices to the original complainant.</li>
            <li><strong>Restoration:</strong> If no legal action is taken within 14 business days of a counter-notice, the material may be restored.</li>
          </ol>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">5. Repeat Infringers</h2>
          <p>
            Tavro maintains a policy of terminating the accounts of users who are determined to be repeat infringers. A repeat infringer is a user who has been notified of IP infringement more than twice or whose content has been removed more than twice. We reserve the right to terminate accounts at our discretion based on the severity and circumstances of the infringement.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">6. Counter-Notices</h2>
          <p>
            If you are a Tavro user whose content was removed due to an IP infringement claim and you believe the removal was mistaken or misidentified, you may submit a counter-notice including:
          </p>
          <ul className="list-disc pl-6 space-y-2">
            <li>Your name, address, and contact information.</li>
            <li>Identification of the removed material and its former location.</li>
            <li>A statement under penalty of perjury that the material was removed in error or misidentification.</li>
            <li>Your consent to the jurisdiction of the courts in Lagos, Nigeria.</li>
            <li>Your physical or electronic signature.</li>
          </ul>
          <p className="mt-3">Submit counter-notices to: <a href="mailto:ip@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">ip@tavro.ng</a></p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">7. Trademark Usage</h2>
          <p>
            You may not use Tavro&rsquo;s trademarks, trade names, logos, or brand elements in any manner that is likely to cause confusion, imply endorsement, or disparage Tavro. Permitted uses include truthful, factual references to Tavro in editorial or informational contexts, provided you include a disclaimer that Tavro is not affiliated with or endorsing your content.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">8. Open Source</h2>
          <p>
            Tavro may incorporate open-source software. Each open-source component is subject to its own licence, and the applicable licence terms are available in our software documentation or upon request. Open-source use does not grant any rights to Tavro&rsquo;s proprietary intellectual property.
          </p>
        </section>

        <section>
          <h2 className="text-2xl font-bold font-display text-charcoal-900 mb-3">9. Contact</h2>
          <ul className="list-disc pl-6 space-y-1">
            <li>IP Agent: <a href="mailto:ip@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">ip@tavro.ng</a></li>
            <li>Legal: <a href="mailto:legal@tavro.ng" className="text-amber-600 hover:text-amber-700 underline">legal@tavro.ng</a></li>
          </ul>
        </section>
      </div>
    </article>
  );
}
