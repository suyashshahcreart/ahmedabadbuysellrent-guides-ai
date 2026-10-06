const marketplaceProd = process.env.ABSR_SITE_ENV === "production";

module.exports = {
  name: "Ahmedabad Property Guides",
  shortName: "Ahmedabad Guides",
  org: "Ahmedabad Buy Sell Rent",
  url: "https://guides.ahmedabadbuysellrent.in",
  env: marketplaceProd ? "production" : "staging",
  noindex: process.env.ABSR_GUIDES_NOINDEX === "1",
  marketplaceUrl: marketplaceProd ? "https://ahmedabadbuysellrent.in" : "https://staging.ahmedabadbuysellrent.in",
  referenceUrl: "https://ahmedabadbuysellrent.in",
  description:
    "Your trusted guide to buying, selling and renting property in Ahmedabad. Locality reviews, comparisons, and practical advice.",
  author: "Ahmedabad Guides",
  assetVersion: "5",
};
