import { SITE_EMAIL, SITE_INSTAGRAM_HANDLE, SITE_INSTAGRAM_URL, SITE_PHONE_DISPLAY } from '@/data/siteContact'

export type PolicySlug = 'refund' | 'privacy' | 'terms' | 'shipping'

export interface PolicySection {
  heading?: string
  paragraphs: string[]
  list?: string[]
}

export interface PolicyDocument {
  slug: PolicySlug
  title: string
  lastUpdated: string
  intro?: string
  sections: PolicySection[]
}

const email = SITE_EMAIL
const instagram = SITE_INSTAGRAM_HANDLE
const instagramUrl = SITE_INSTAGRAM_URL

export const POLICIES: Record<PolicySlug, PolicyDocument> = {
  refund: {
    slug: 'refund',
    title: 'Refund & Return Policy',
    lastUpdated: 'July 2026',
    intro: 'At The Minimark, we want you to be happy with your order. Please read this policy carefully before purchasing.',
    sections: [
      {
        heading: 'Damaged or incorrect items',
        paragraphs: [
          "If your order arrives damaged, defective, or incorrect, we'll gladly send a replacement — no extra cost for the product itself.",
          'To process this quickly: share a clear, unedited unboxing video showing the sealed parcel being opened from the start. Email it to ' +
            email +
            ' within 24 hours of delivery, along with your order number and issue details.',
          'Claims without an unboxing video will not be eligible for a replacement.',
        ],
      },
      {
        heading: 'Returns',
        paragraphs: [
          'Returns are accepted only within 24 hours of delivery.',
          'An unedited unboxing video is mandatory for any return request.',
          'The item must be unused and in its original packaging.',
          'Customers must pay an additional ₹70 towards return delivery / reverse shipping charges.',
        ],
      },
      {
        heading: 'No COD & no cancellations',
        paragraphs: [
          'We do not offer Cash on Delivery (COD).',
          'Orders cannot be cancelled once placed.',
        ],
      },
      {
        heading: 'Order verification',
        paragraphs: [
          'Please inspect your parcel at the time of delivery. Recording an unboxing video is strongly recommended for all orders, as it is required for any damage or return claim.',
        ],
      },
      {
        heading: 'Need help?',
        paragraphs: [
          `For any issue, email us at ${email} or reach out on Instagram ${instagram} with your order number and details.`,
          'Thank you for supporting The Minimark.',
        ],
      },
    ],
  },
  privacy: {
    slug: 'privacy',
    title: 'Privacy Policy',
    lastUpdated: 'July 2026',
    intro:
      'At The Minimark, we respect your privacy and are committed to protecting your personal information. This policy explains what we collect, how we use it, and how we keep it safe.',
    sections: [
      {
        heading: 'What we collect',
        list: [
          'Name, contact details, shipping address, and payment information.',
          'Order history, customer support communications, and website usage data (such as pages visited and items added to cart).',
        ],
        paragraphs: [],
      },
      {
        heading: 'How we use it',
        list: [
          'To process, pack, and deliver your orders.',
          'To provide customer support and respond to your queries.',
          'To improve our products, website, and overall shopping experience.',
          'To send offers, updates, and promotions — you can opt out anytime.',
        ],
        paragraphs: [],
      },
      {
        heading: 'We never',
        list: [
          'Sell or rent your personal data to anyone.',
          'Share your data with third parties, except trusted partners directly involved in fulfilling your order — such as payment gateways, courier/delivery partners, and our website platform.',
        ],
        paragraphs: [],
      },
      {
        heading: 'Security',
        paragraphs: [
          'We use secure systems and encryption to protect your personal and payment information. While no online system can be 100% guaranteed secure, we take reasonable steps to keep your data safe.',
        ],
      },
      {
        heading: 'Your choices',
        list: [
          'You can unsubscribe from promotional emails at any time using the unsubscribe link.',
          'You can contact us to request access to, correction of, or deletion of your personal information.',
        ],
        paragraphs: [],
      },
      {
        heading: 'Contact us',
        paragraphs: [
          `If you have any questions about this Privacy Policy, reach out at ${email} or on Instagram ${instagram}.`,
        ],
      },
    ],
  },
  terms: {
    slug: 'terms',
    title: 'Terms of Service',
    lastUpdated: 'July 2026',
    intro:
      'Welcome to The Minimark! These Terms and Conditions outline the rules for using our website and purchasing our products. By accessing or using our website, you agree to comply with and be bound by the following terms. If you disagree with any part of these terms, please do not use our website.',
    sections: [
      {
        heading: '1. Products & personalisation',
        paragraphs: [
          'Some of our items may be custom-made or personalised. Please double-check all details (names, dates, spellings, photos) before placing your order.',
          'We are not responsible for errors submitted by the customer at the time of order.',
        ],
      },
      {
        heading: '2. Order processing & delivery',
        list: [
          'Orders are processed only after successful payment.',
          'Production/dispatch time is typically 3–5 working days.',
          'Delivery timelines vary by location; courier partner delays are beyond our control.',
          'We do not offer Cash on Delivery (COD).',
        ],
        paragraphs: [],
      },
      {
        heading: '3. Returns, replacements & cancellations',
        paragraphs: [
          'Orders cannot be cancelled once placed.',
          'Returns are accepted only within 24 hours of delivery, and require an unedited unboxing video showing the sealed parcel being opened from the start. The item must be unused and in its original packaging. Customers must pay an additional ₹70 towards return delivery/reverse shipping charges.',
          "If your item arrives damaged or incorrect, share the unboxing video along with your order number and issue details within 24 hours of delivery, and we'll send a replacement — no extra cost for the product itself.",
          'Claims without an unboxing video are not eligible for return or replacement.',
          'For full details, see our Refund & Return Policy.',
        ],
      },
      {
        heading: '4. Pricing & payment',
        list: [
          'All prices are listed in INR and inclusive of applicable taxes unless stated otherwise.',
          'We reserve the right to change prices at any time.',
          'Payments are processed securely via a third-party payment provider.',
        ],
        paragraphs: [],
      },
      {
        heading: '5. Intellectual property',
        paragraphs: [
          'All designs, product photos, content, and branding are the intellectual property of The Minimark. You may not reproduce, copy, or use any of our materials without written permission.',
        ],
      },
      {
        heading: '6. Privacy',
        paragraphs: ['We respect your privacy. Please refer to our Privacy Policy to understand how we collect and use your information.'],
      },
      {
        heading: '7. Contact information',
        paragraphs: [
          `Email: ${email}`,
          `Instagram: ${instagram} (${instagramUrl})`,
          'Thank you for shopping with The Minimark.',
        ],
      },
    ],
  },
  shipping: {
    slug: 'shipping',
    title: 'Shipping Policy',
    lastUpdated: 'July 2026',
    intro: 'Thank you for shopping with The Minimark. Below are the details regarding order processing, shipping timelines, and delivery terms.',
    sections: [
      {
        heading: '1. Order processing time',
        list: [
          'All orders are processed and dispatched within 3–5 business days after order confirmation.',
          'Business days exclude Sundays and public holidays.',
          'During sales, festive seasons, or high-volume periods, dispatch timelines may be slightly extended.',
        ],
        paragraphs: [],
      },
      {
        heading: '2. Delivery timeline',
        list: [
          'Once dispatched, delivery typically takes 4–7 business days, depending on your location.',
          'Orders are shipped through trusted logistics partners.',
          "We'll share a tracking link once your order is on the way.",
          'Delivery timelines are estimates and may vary due to courier operations or regional constraints.',
        ],
        paragraphs: [],
      },
      {
        heading: '3. Incorrect address or contact details',
        paragraphs: [
          'If delivery fails due to an incorrect or incomplete address, incorrect phone number, or customer unavailability, the order may be returned to us (RTO — Return to Origin). In such cases a re-shipping fee will be applicable before the order is dispatched again. Re-dispatch may take an additional 3–5 business days after payment of re-shipping charges.',
        ],
      },
      {
        heading: '4. Delivery delays',
        paragraphs: [
          'While we work with reliable courier partners, The Minimark is not responsible for delays caused by logistics disruptions, weather conditions, public holidays, or incorrect details provided by the customer. Refunds will not be issued for courier-related delays.',
        ],
      },
      {
        heading: '5. Changes after dispatch',
        paragraphs: [
          'Once an order has been shipped, we are unable to modify the address, redirect, or hold the shipment. Please coordinate directly with the logistics provider for delivery assistance.',
        ],
      },
      {
        heading: '6. Order tracking',
        paragraphs: [
          'Tracking details will be shared once your order is dispatched. You can also look up your order on our Track Order page using your order number and email. You are responsible for monitoring the shipment using the tracking link provided.',
        ],
      },
      {
        heading: '7. Need it earlier?',
        paragraphs: [
          `If you have a specific date in mind, message us on WhatsApp at ${SITE_PHONE_DISPLAY} or Instagram ${instagram} before ordering, and we'll try our best to make it happen (express shipping charges may apply).`,
        ],
      },
      {
        heading: '8. International shipping',
        paragraphs: ['Currently not available. Domestic shipping within India only.'],
      },
      {
        heading: 'Questions?',
        paragraphs: [
          `If you have any shipping-related questions, reach out at ${email} or on Instagram ${instagram}.`,
          'Thank you for shopping with The Minimark.',
        ],
      },
    ],
  },
}

export const POLICY_NAV: { slug: PolicySlug; label: string }[] = [
  { slug: 'refund', label: 'Refund & Return Policy' },
  { slug: 'privacy', label: 'Privacy Policy' },
  { slug: 'terms', label: 'Terms of Service' },
  { slug: 'shipping', label: 'Shipping Policy' },
]

export function policyBySlug(slug: string): PolicyDocument | null {
  if (slug in POLICIES) return POLICIES[slug as PolicySlug]
  return null
}
