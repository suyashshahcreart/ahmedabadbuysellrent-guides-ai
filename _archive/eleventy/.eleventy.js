const markdownIt = require("markdown-it");
const markdownItAnchor = require("markdown-it-anchor");

module.exports = function (eleventyConfig) {
  eleventyConfig.addPassthroughCopy({ "src/css": "css" });
  eleventyConfig.addPassthroughCopy({ "src/js": "js" });
  eleventyConfig.addPassthroughCopy({ "src/assets": "assets" });
  eleventyConfig.addPassthroughCopy({ "src/favicon.svg": "favicon.svg" });
  eleventyConfig.addPassthroughCopy({ "src/robots.txt": "robots.txt" });

  const md = markdownIt({ html: true, linkify: true }).use(markdownItAnchor, {
    permalink: false,
    slugify: (s) =>
      String(s)
        .toLowerCase()
        .replace(/[^\w]+/g, "-")
        .replace(/(^-|-$)/g, ""),
  });
  eleventyConfig.setLibrary("md", md);

  eleventyConfig.addCollection("guides", (api) =>
    api.getFilteredByGlob("src/guides/*.md").sort((a, b) => b.date - a.date)
  );
  eleventyConfig.addCollection("comparisons", (api) =>
    api.getFilteredByGlob("src/compare/*.md").sort((a, b) => b.date - a.date)
  );
  eleventyConfig.addCollection("articles", (api) =>
    [...api.getFilteredByGlob("src/guides/*.md"), ...api.getFilteredByGlob("src/compare/*.md")].sort(
      (a, b) => b.date - a.date
    )
  );
  eleventyConfig.addCollection("areas", (api) =>
    api.getFilteredByGlob("src/areas/*.md").sort((a, b) => (a.data.order || 0) - (b.data.order || 0))
  );

  eleventyConfig.addFilter("isoDate", (d) => {
    if (!d) return "";
    const date = d instanceof Date ? d : new Date(d);
    return date.toISOString().slice(0, 10);
  });
  eleventyConfig.addFilter("displayDate", (d) => {
    if (!d) return "";
    const date = d instanceof Date ? d : new Date(d);
    return date.toLocaleDateString("en-IN", { day: "numeric", month: "short", year: "numeric" });
  });
  eleventyConfig.addFilter("json", (v) => JSON.stringify(v));
  eleventyConfig.addFilter("absolute", (path, site) => {
    if (!path) return site.url + "/";
    if (path.startsWith("http")) return path;
    return site.url + (path.startsWith("/") ? path : "/" + path);
  });
  eleventyConfig.addFilter("byCategory", (items, slug) =>
    (items || []).filter((item) => item.data.category === slug)
  );
  eleventyConfig.addFilter("featured", (items) => (items || []).filter((item) => item.data.featured));
  eleventyConfig.addFilter("trending", (items) => (items || []).filter((item) => item.data.trending));
  eleventyConfig.addFilter("take", (items, n) => (items || []).slice(0, n));
  eleventyConfig.addFilter("skip", (items, n) => (items || []).slice(n));
  eleventyConfig.addFilter("relatedTo", (collection, slug, cat) => {
    const others = (collection || []).filter((item) => item.fileSlug !== slug);
    const same = others.filter((item) => item.data && item.data.category === cat);
    const rest = others.filter((item) => !item.data || item.data.category !== cat);
    return [...same, ...rest].slice(0, 3);
  });
  eleventyConfig.addFilter("categoryLabel", (slug, categories) => {
    const found = (categories || []).find((c) => c.slug === slug);
    return found ? found.title : slug;
  });
  eleventyConfig.addFilter("prevNext", (collection, url) => {
    const list = collection || [];
    const i = list.findIndex((item) => item.url === url);
    return {
      prev: i > 0 ? list[i - 1] : null,
      next: i >= 0 && i < list.length - 1 ? list[i + 1] : null,
    };
  });
  eleventyConfig.addFilter("headings", (content) => {
    const matches = [...String(content || "").matchAll(/<h2[^>]*id="([^"]+)"[^>]*>(.*?)<\/h2>/gi)];
    return matches.map((m) => ({
      id: m[1],
      text: m[2].replace(/<[^>]+>/g, "").trim(),
    }));
  });

  eleventyConfig.addShortcode("year", () => String(new Date().getFullYear()));

  return {
    dir: { input: "src", output: "public", includes: "_includes", data: "_data" },
    htmlTemplateEngine: "njk",
    markdownTemplateEngine: "njk",
  };
};
