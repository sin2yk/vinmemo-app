You are working on a small web application project (PHP + HTML/CSS/JS + MySQL).  
Please review and improve **the entire project folder** in a careful, non-breaking way.

### Goals
- Fix obvious bugs and runtime errors.
- Improve readability and structure of the code.
- Make the UI a bit more consistent and usable, especially on mobile.
- Add small safety checks (validation, error handling) where they are clearly missing.

### What you MAY do
- Refactor functions and internal variables for clarity (only if it doesn’t break external usage).
- Remove dead code, unused variables, and redundant comments.
- Normalize indentation, spacing and basic formatting.
- Add short, helpful comments where the intent of the code is not obvious.
- Improve small UI details (spacing, font size, basic responsive layout) as long as the existing design concept is preserved.
- Add simple guard clauses (e.g., null/empty checks) to avoid warnings and notices.

### What you MUST NOT do
- Do NOT change file names, folder structure, or URLs.
- Do NOT change HTML `name` attributes or form field names.
- Do NOT change request parameter names (GET/POST) used between pages.
- Do NOT rename database tables or columns, or change the basic SQL schema.
- Do NOT introduce new external dependencies or frameworks.
- Do NOT rewrite the whole architecture or drastically change how pages are routed.

### Style & Scope
- Keep the overall behavior of the app the same: same pages, same flows, same data saved.
- Prefer many small, safe improvements over one big risky rewrite.
- If you make a non-obvious change, add a short comment explaining *why*.
- Apply these rules consistently to all relevant files in the project folder.

With these constraints in mind, go through the entire project and:
1. Fix clear bugs and fragile spots.
2. Gently refactor and clean up the code.
3. Make minor but meaningful UX improvements, especially for mobile users.
