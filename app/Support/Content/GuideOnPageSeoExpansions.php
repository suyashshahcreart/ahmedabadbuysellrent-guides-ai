<?php

namespace App\Support\Content;

/**
 * Extra long-form HTML blocks to bring each guide to ~1200–1500 words.
 */
final class GuideOnPageSeoExpansions
{
    public static function for(string $slug, string $title): string
    {
        $blocks = self::blocks();
        $extra = $blocks[$slug] ?? self::fallback($title);

        return $extra;
    }

    private static function fallback(string $title): string
    {
        return '<h2>Detailed notes for '.$title.'</h2><p>Use this guide as a working checklist. Validate every locality claim with a peak-hour visit, every price claim with comparable evidence, and every document claim with independent legal review.</p>';
    }

    private static function wrap(string $h2, array $paras, array $list = [], array $h3s = []): string
    {
        $html = '<h2>'.$h2.'</h2>';
        foreach ($paras as $p) {
            $html .= '<p>'.$p.'</p>';
        }
        if ($list) {
            $html .= '<ul>';
            foreach ($list as $i) {
                $html .= '<li>'.$i.'</li>';
            }
            $html .= '</ul>';
        }
        foreach ($h3s as $h3) {
            $html .= '<h3>'.$h3[0].'</h3>';
            foreach ($h3[1] as $p) {
                $html .= '<p>'.$p.'</p>';
            }
        }

        return $html;
    }

    private static function blocks(): array
    {
        return [
            'ahmedabad-home-loan-guide' =>
                self::wrap('Building a lender-ready file in Ahmedabad', [
                    'A clean file reduces back-and-forth with bank credit teams. Create a single digital folder with KYC, income proofs, bank statements, and a one-page summary of your employment history. If you are salaried in a recognised company, highlight joining date, fixed vs variable pay, and existing EMI obligations honestly. If you are self-employed in Gujarat, organise GST returns, CA-certified computation, and business ownership proofs before the first lender meeting.',
                    'Many first-time buyers in Ahmedabad lose weeks because statements have unexplained cash deposits or bounced EMIs. Write short explanations for unusual credits in advance. Keep credit card utilisation moderate for at least one or two billing cycles before applying. A little preparation often improves both approval odds and the interest band you are offered.',
                ], [
                    '<strong>Tip:</strong> Submit the same facts to every lender—inconsistencies across applications create avoidable queries.',
                    'Ask each lender for a written fee schedule covering processing, legal, and valuation charges.',
                    'Track sanction validity dates so your property search window stays realistic.',
                ], [
                    ['Stage disbursement and possession timing', [
                        'For under-construction projects, align builder demand notices with lender stage-disbursement rules. Confirm whether your co-applicant must sign at every stage. For ready homes, valuation and technical checks can still take time—especially if society papers or parking allotments are unclear.',
                        'Do not schedule registration the day after verbal sanction. Keep buffer for legal opinion, insurance, and final technical clearance. Buyers targeting west Ahmedabad inventory should also plan for higher stamp-duty outflows in the same month as disbursement.',
                    ]],
                    ['Rate type, prepayment, and reset clauses', [
                        'Read whether your loan is linked to an external benchmark and how quickly resets flow through. Ask about prepayment charges on floating-rate loans, part-prepayment process in net banking, and whether converting from fixed to floating later is allowed. Over a 20-year tenure, small clause differences matter more than a temporary processing-fee waiver.',
                        'If you expect annual bonuses, decide a part-prepayment habit on day one. Reducing principal in the early years cuts total interest sharply. Keep an EMI sweep strategy that never starves your emergency fund.',
                    ]],
                ]).
                self::wrap('Local Ahmedabad scenarios to plan for', [
                    'Scenario one: you are shortlisting Gota for budget and Thaltej for lifestyle. Get pre-approved for a max EMI, then create two property budgets—one for each band—including interiors. Scenario two: you are buying a resale flat with an existing society history. Share society documents early with the lender’s legal team so valuation is not delayed at the end.',
                    'Scenario three: you are an NRI co-applicant supporting a resident buyer. Clarify whose income is considered, which documents must be attested, and how power of attorney interacts with bank signing requirements. Scenario four: you are self-employed with variable monthly credits. Provide a clear business narrative and choose lenders comfortable with your profile rather than forcing a PSU-only approach.',
                ], [], [
                    ['Final borrower checklist before you pay token', [
                        'Confirm sanction amount, ROI, tenure, and fee deductions in writing. Recalculate EMI after any down-payment change. Ensure the property is on the lender approved list if required. Keep funds for stamp duty separate from the EMI buffer. With these habits, a home loan becomes a controlled process instead of a last-minute scramble.',
                    ]],
                ]),

            'ahmedabad-property-market-notes' =>
                self::wrap('Reading supply, launches and secondary stock', [
                    'Ahmedabad’s western growth keeps producing new launches, but absorption is uneven. A crowded launch calendar in one micro-market can slow price discovery even when city sentiment sounds optimistic. Track how many unsold units sit in competing towers within a short radius, and whether developers are pushing schemes that signal inventory pressure.',
                    'Secondary stock tells a different story. Older societies near established schools often sell through end-user networks with less drama than glossy launches. However, secondary buyers must price renovation and parking reality. A “cheaper” resale can become expensive after electrical rewiring and bathroom waterproofing.',
                ], [
                    'Watch for <strong>construction quality variance</strong> between phases of the same project.',
                    'Treat brochure density maps as marketing, not due diligence.',
                    'Speak to at least two resident families before trusting amenity claims.',
                ], [
                    ['Capital values versus rental values', [
                        'Capital appreciation narratives dominate conversations, yet rental values reveal true demand. If rents are flat while asking prices climb, investors should pause. End-users can still buy for lifestyle reasons, but they should know they are paying for use value, not instant paper gains.',
                        'In employment-linked pockets, rental demand can stay resilient even through quieter sales seasons. In speculative edges, both rent and resale may soften together. Your market notes should always separate these behaviours.',
                    ]],
                    ['Practical monthly market routine for serious buyers', [
                        'Once a week, review new listings in your shortlist pin codes, note price cuts, and record visit feedback in a simple scorecard. Once a month, re-check loan rates and your EMI ceiling. This rhythm prevents panic decisions when a seller says “another buyer is coming tomorrow.”',
                        'Use festive seasons carefully. Discounts can be real, but paperwork shortcuts are not a bargain. The best Ahmedabad purchases combine market awareness with boring, complete diligence.',
                    ]],
                ]),

            'buy-vs-rent-ahmedabad' =>
                self::wrap('Worked example thinking for Ahmedabad households', [
                    'Imagine two households. Household A can comfortably place a down payment and still keep six months of expenses in liquid form. They expect to stay near west Ahmedabad schools for seven years. Buying can make sense if EMI stress tests pass. Household B has a strong income but may relocate in two years and would empty investments for a down payment. Renting a better micro-market is usually wiser.',
                    'Now add numbers in your own sheet: target rent, target EMI, closing costs, and expected annual rent hike. Extend the sheet to year five and year eight. If buying only wins when you assume aggressive appreciation, be honest that you are making a market bet, not a pure housing decision.',
                ], [], [
                    ['Flexibility premium of renting', [
                        'Renting lets you change localities as careers shift between SG-side offices and more central roles. It also lets you upgrade society quality without selling. In a city where western sub-markets evolve quickly, that flexibility has financial value.',
                        'Renting is not “throwing money away” if it preserves optionality and prevents a leveraged purchase you cannot hold through income volatility. Ownership is not automatically disciplined saving if maintenance and interest consume cash that could have built a diversified corpus.',
                    ]],
                    ['Hybrid strategies many Ahmedabad families use', [
                        'Some families rent in a premium pocket while buying a smaller investment unit elsewhere. Others buy a practical home first and plan a later upgrade. A third path is to delay buying until pre-approval and emergency funds are both solid. There is no moral hierarchy—only fit.',
                        'Revisit the buy-vs-rent decision after major life events: marriage, child schooling choices, business formation, or inheritance liquidity. A choice that was right at age twenty-eight may be wrong at thirty-five.',
                    ]],
                ]),

            'central-zone-living' =>
                self::wrap('Everyday systems: water, waste, safety and community', [
                    'Central living succeeds when basic systems work. Ask neighbours about summer water pressure, drainage during heavy rain, and waste collection consistency. In denser fabrics, one weak point affects many households. A charming street loses appeal if monsoon mornings become stressful.',
                    'Community is a genuine asset of older Ahmedabad neighbourhoods. Festivals, trust networks, and walkable errands create a lifestyle towers rarely replicate. At the same time, privacy expectations differ. Visit during celebrations and normal weekdays to understand the social rhythm you are joining.',
                ], [
                    'Confirm <strong>fire access</strong> and emergency vehicle approach where streets are narrow.',
                    'Evaluate mobile network and broadband reliability inside the home.',
                    'Check whether redevelopment conversations could affect near-term plans.',
                ], [
                    ['Renovation budgeting for central homes', [
                        'Older homes often need kitchen and bathroom refreshes, safer electrical boards, and improved waterproofing. Get contractor estimates before you finalise purchase budgets. Buyers who keep only EMI capacity and forget renovation cash get stuck in half-finished homes.',
                        'If you love heritage character, preserve what is structurally sound rather than forcing a mall-flat interior into an incompatible shell. Good central-zone renovations respect ventilation and local materials while upgrading safety.',
                    ]],
                    ['Investment and exit notes', [
                        'Investor exits in central pockets depend on finding the right end-user. Pricing must respect renovation needs. Liquidity is available, but not always as fast as western gated-society stock. Keep expectations calibrated and documents immaculate to help the next buyer move quickly.',
                    ]],
                ]),

            'evaluating-apartment-interiors' =>
                self::wrap('Room-by-room evaluation method', [
                    'Begin at the entrance: door thickness, lock quality, and shoe/storage space. Move to the living room for proportion, TV wall options, and whether furniture layouts block circulation. In bedrooms, measure wardrobe depth assumptions and confirm whether queen beds leave comfortable walkways. Children rooms need study light and safer window grills.',
                    'In the kitchen, ignore decorative backsplashes until you confirm shaft smell control, trap cleanliness, and service-access panels. In bathrooms, run hot and cold water, test flush, and look above false ceilings if accessible for old leak clues. Balconies deserve a waterproofing and railing safety check, especially in taller west Ahmedabad towers.',
                ], [], [
                    ['Materials that age well in local climate', [
                        'Ahmedabad heat and dust punish cheap hardware. Prefer durable flooring in high-traffic zones, quality bathroom fixtures, and window systems that seal well without trapping condensation. Ask which materials are builder-standard versus buyer upgrades, and keep warranties.',
                        'For rentals, choose interiors that survive tenant turnover: stone or good vitrified flooring, washable paints, and modular systems with replaceable parts. Over-custom luxury finishes can reduce tenant pool and increase maintenance calls.',
                    ]],
                    ['Scoring template you can reuse', [
                        'Give each flat marks out of ten for light, ventilation, kitchen function, bathroom health, electrical readiness, acoustic comfort, and storage. Reject any flat that fails bathroom health or electrical readiness regardless of how pretty the living room looks. This scoring habit prevents emotional overrides during sales pressure.',
                    ]],
                ]),

            'first-time-buyer-checklist' =>
                self::wrap('Thirty-day action plan for first-time buyers', [
                    'Week one: close your budget, pull credit reports, and start pre-approval conversations. Week two: finalise two localities and book clustered site visits. Week three: complete second visits, shortlist two units, and open legal review. Week four: negotiate, align loan process, and prepare token only after checklist gates are green.',
                    'This cadence keeps momentum without recklessness. Buyers who wander across the city for months often either overpay from fatigue or abandon good options. A time-boxed plan creates healthy urgency.',
                ], [
                    'Keep a shared family scorecard so decisions are not based on one person’s showroom impression.',
                    'Save every quotation, brochure, and WhatsApp assurance as screenshots for later comparison.',
                    'Appoint one point person for banker, lawyer, and broker coordination.',
                ], [
                    ['People and professionals you may need', [
                        'A transparent broker can open inventory, but you still own diligence. A lawyer protects title. A banker structures the loan. A civil inspector is optional but valuable on older stock. You do not need a huge team—you need accountable roles and written scopes.',
                        'Avoid depending on a single channel partner for legal comfort and pricing advice. Separation of roles reduces conflict of interest and improves outcomes for first-time buyers.',
                    ]],
                    ['Emotional pitfalls to avoid', [
                        'Do not let festival offers dictate tenure choices. Do not treat sample flats as the delivered product without specification lists. Do not hide true EMI discomfort from your co-buyer. The best first purchase is the one you can hold calmly through ordinary life surprises.',
                    ]],
                ]),

            'investing-in-ahmedabad-property' =>
                self::wrap('Portfolio role of Ahmedabad real estate', [
                    'Treat a city property as one asset in a broader portfolio. If most of your net worth will sit in a single leveraged flat, risk concentration is high. Diversified investors size tickets so that vacancy or repair cycles do not threaten lifestyle cash flow.',
                    'Ahmedabad can play the role of a yield-supporting or family-anchor asset. It is less convincing as a short-flip vehicle unless you have rare access to mispriced inventory and exit demand. Build models with base, bull, and bear cases for rent and exit timing.',
                ], [], [
                    ['Operating cadence after you buy', [
                        'Set calendar reminders for society AGMs, insurance renewals, rent escalations, and maintenance audits. Professionalise rent collection and repairs with written processes. Investors who “set and forget” often discover neglected seepage or unpaid dues too late.',
                        'Track net yield annually. If a unit chronically underperforms, decide whether to renovate for rent, refinance, or exit. Sentiment should not trap capital indefinitely.',
                    ]],
                    ['Taxes and advisor touchpoints', [
                        'Work with a CA on interest deductibility, rental income reporting, and capital gains planning at exit. Keep invoices for improvements that may support cost basis. Good records increase both compliance comfort and sale readiness.',
                    ]],
                ]),

            'new-west-zone' =>
                self::wrap('Infrastructure watching list for New West buyers', [
                    'Before buying into a growth pocket, list the infrastructure you are depending on: road widening, approach quality, water sourcing, drainage, and nearby social amenities. Ask what is already live versus promised. Promises can create value, but only live infrastructure should be fully priced into your offer.',
                    'Visit during construction peaks if neighbouring projects are active. Dust, truck movement, and temporary access changes affect daily life for years. Families with infants or elders should weigh this carefully against the appeal of newer floor plans.',
                ], [
                    'Prefer projects with <strong>completed external access</strong> over those relying on temporary cuts.',
                    'Check distance to reliable hospitals and groceries for weekly needs.',
                    'Evaluate whether your society can function if surrounding retail is delayed.',
                ], [
                    ['Community formation in new societies', [
                        'Newer societies need active resident groups to set security and maintenance culture. Speak to the association, not only the sales team. A beautiful clubhouse with weak governance becomes expensive quickly.',
                        'If you are an early resident, budget time for community building or accept that service levels may lag. Some buyers prefer slightly older New West societies where operations have stabilised.',
                    ]],
                ]),

            'nri-property-ahmedabad' =>
                self::wrap('Remote purchase workflow that reduces risk', [
                    'A practical NRI workflow looks like this: shortlist remotely with video walkthroughs, appoint lawyer and POA agent, complete title diligence, lock commercial terms, route funds through proper channels, register, and then set up property management. Skipping order creates expensive corrections.',
                    'Video tours help, but they miss smell, noise, and neighbourhood feel. If you cannot travel, ask a trusted family member to complete an unscripted visit at peak hour and share unedited video. Require sellers or builders to answer a written question list covering water, parking, and dues.',
                ], [], [
                    ['Banking, nomination and estate hygiene', [
                        'Keep nomination and joint-holding structures updated. Store digital copies of deeds in multiple locations. Inform your local manager how emergency repairs are authorised and funded. Clear estate hygiene prevents disputes and speeds future sales.',
                        'If parents will occupy the home, document who pays maintenance and utilities. Many family conflicts start from informal assumptions. Written clarity protects relationships and assets.',
                    ]],
                    ['Currency and timing considerations', [
                        'INR movement affects affordability for income earned abroad. Avoid compressing research because a rate looks temporarily favourable. Quality diligence still beats opportunistic speed for long-hold assets.',
                    ]],
                ]),

            'property-documents-ahmedabad' =>
                self::wrap('How to work with your property lawyer', [
                    'Share the full document set at once, including prior chain links and society correspondence. Ask for a written opinion listing risks, gaps, and conditions precedent before token or agreement. Request plain-language explanations for any finding that could affect possession, parking, or mortgageability.',
                    'Budget enough time for the lawyer to verify rather than demanding same-day clearance. Sellers who refuse reasonable diligence are giving you information—often that you should walk away.',
                ], [
                    'Maintain a <strong>document index</strong> with file names and dates.',
                    'Verify that area figures match across agreement, society papers, and marketing sheets.',
                    'Confirm signatory authority for all co-owners and representative sellers.',
                ], [
                    ['Digital hygiene after registration', [
                        'Scan the registered deed at high resolution, store certified copies safely, and update bank/society records promptly. If a loan exists, track charge creation and later satisfaction documents. Future you—and future buyers—will thank present you for organised files.',
                    ]],
                ]),

            'renting-gota' =>
                self::wrap('Neighbourhood routines that shape Gota rentals', [
                    'Spend a weekend living the routine: groceries, pharmacies, evening walks, and cab availability. Speak with security staff about water tanker frequency and visitor processes. Small operational details decide whether a “good deal” remains good after a month.',
                    'If you have school-going children, trial the morning route. If you work odd hours, check lighting and security on the approach road. Gota’s appeal is practical; your inspection should be equally practical.',
                ], [], [
                    ['Negotiation levers for tenants', [
                        'Longer lock-in can sometimes reduce rent or deposit, but only if you are sure. Asking for minor repairs before move-in is fair. Request a clear inventory list for fans, geysers, and modular kitchen components. Avoid oral promises about future painting or appliance replacement.',
                        'If multiple similar flats are vacant in the same society, you have more leverage. If vacancy is scarce, decide faster on a sound unit rather than hunting forever for a perfect discount.',
                    ]],
                ]),

            'renting-navrangpura' =>
                self::wrap('Balancing centrality with comfort in Navrangpura', [
                    'Centrality can mean quicker errands and shorter trips to many institutions, yet it can also mean denser traffic and older building systems. Your rental success depends on choosing the right building, not only the right locality name.',
                    'Upper floors may offer better light but depend on lift reliability. Lower floors may be easier for elders but noisier. Corner units can ventilate better. These micro choices matter more in older central stock than in uniform new towers.',
                ], [], [
                    ['Tenant documentation and safety', [
                        'Complete police verification processes if required, keep agreement copies, and ensure you understand guest policies. For shared flats, clarify bill splits and notice rules among roommates in writing to avoid mid-year disputes.',
                        'Identify nearest hospitals and late-hour transport options during your first week. Central addresses feel convenient until the first late meeting coincides with poor last-mile availability.',
                    ]],
                ]),

            'selling-property-ahmedabad' =>
                self::wrap('Marketing sequence that attracts serious buyers', [
                    'First prepare documents and pricing. Then create accurate photos and a factual listing. Then activate broker and portal channels. Sellers who market first and gather papers later lose hot buyers during diligence delays.',
                    'Respond to enquiries with a standard information pack: society name, carpet details, parking type, loan status, maintenance amount, and available visit slots. Professional responses filter casual browsers and build credibility.',
                ], [], [
                    ['Handling price feedback without panic', [
                        'If qualified buyers repeatedly say you are above market, reassess within two weeks rather than waiting three months. Small strategic cuts early often net more than large cuts after a listing goes stale.',
                        'Distinguish between bargain hunting and genuine market signals. One low offer is noise; repeated similar feedback is data.',
                    ]],
                    ['After-sale transitions', [
                        'Plan your move-out so the buyer gets a clean unit. Cancel or transfer utilities thoughtfully. Inform society of ownership change and settle dues. A smooth handover protects your reputation and reduces post-sale disputes.',
                    ]],
                ]),

            'thaltej-buying-guide' =>
                self::wrap('Thaltej micro-pocket evaluation', [
                    'Thaltej is not one street. Interior society experiences differ from homes closer to heavier traffic corridors. Evaluate approach roads, flooding history if any, and the quality of daily retail within a short drive. A premium pin code still needs ordinary livability.',
                    'When comparing two Thaltej societies, ask for maintenance budgets, major repair history, and sinking fund philosophy. Premium positioning without funding discipline becomes a future special-charge problem.',
                ], [], [
                    ['Buyer personas that fit Thaltej', [
                        'Upgraders seeking school adjacency, dual-income households with west-side jobs, and residents who want established social infrastructure often fit well. Pure yield hunters must run stricter math because entry tickets can be high.',
                        'If your budget is tight for Thaltej’s stronger societies, compare adjacent localities rather than forcing a compromised buy. A stronger unit nearby can beat a weak Thaltej address.',
                    ]],
                ]),

            'west-zone-overview' =>
                self::wrap('How to tour West Zone efficiently', [
                    'Cluster visits geographically. Do Satellite societies in one block, Bodakdev in another, and Prahlad Nagar or Thaltej in a third. Take notes immediately after each society while memory is fresh. Photos of notice boards and parking basements help later comparison.',
                    'Eat a meal in the area and observe evening traffic. West Zone marketing photos are daytime bright; your life includes 7 PM returns and Sunday market runs. Tour like a resident, not a tourist.',
                ], [], [
                    ['Long-term ownership considerations', [
                        'Think about ageing in the home, school years, and possible parent co-living. Lift reliability, hospital access, and walkable essentials become more important over a decade than launch-party amenities.',
                        'West Zone ownership usually means higher absolute maintenance. Include that in long-term affordability, especially for retirement planning or single-income phases.',
                    ]],
                ]),

            'bodakdev-vs-satellite' =>
                self::wrap('Side-by-side scorecard you can copy', [
                    'Create columns for Bodakdev and Satellite. Rows: peak office commute, school commute, grocery ease, society shortlist quality, noise, parking confidence, and budget stretch. Score 1–5. Add notes for deal-breakers. The locality with higher total and no deal-breaker wins.',
                    'If scores tie, prefer the option with better society governance and clearer documents. Ties are common; operational quality should break them.',
                ], [], [
                    ['Examples of false comparisons', [
                        'Comparing a premium Bodakdev society to a weak Satellite building is not a locality comparison. Compare like with like. Likewise, do not compare a freshly renovated flat to an unrenovated one without adjusting budgets.',
                        'Avoid deciding from one Sunday visit. Repeat the winning locality visit on a weekday morning before you pay token.',
                    ]],
                ]),

            'sg-highway-vs-cg-road' =>
                self::wrap('Noise, air and access: the corridor reality check', [
                    'Main-corridor living can mean faster cab pickups and better transit access, but also horn density and slower sleep for sensitive households. Interior societies a short drive from the corridor often capture access benefits with fewer downsides.',
                    'If your work involves frequent client meetings across the city, test which corridor reduces total weekly travel time. The “famous” road is less important than cumulative minutes saved.',
                ], [], [
                    ['Commercial versus residential decision split', [
                        'A business may benefit from CG Road visibility while the founder’s family home is better in a quieter west society with SG access. Do not force one address to satisfy both goals if the trade-offs conflict.',
                        'Investors should also split analysis: commercial yield drivers differ from residential tenant drivers. Keep models separate to avoid confused purchases.',
                    ]],
                ]),

            'thaltej-vs-bodakdev' =>
                self::wrap('Negotiation and timing across both localities', [
                    'In both Thaltej and Bodakdev, ready buyers with clean paperwork gain leverage. Sellers of vacant, well-kept units may be firmer on price; sellers with urgency may trade price for speed. Know which dynamic you are in before you negotiate.',
                    'Seasonality and listing age matter. A property listed far above comps for months is different from a fresh, fairly priced unit. Your offer strategy should reflect that data.',
                ], [], [
                    ['Final shortlist workshop', [
                        'Bring your top flat from each locality into one evening discussion with co-buyers. Review scorecards, EMI comfort, and renovation needs. Sleep on it, then decide. Premium west purchases deserve deliberate tempo—not showroom adrenaline.',
                        'Once you choose, stop reopening the comparison unless a material diligence issue appears. Endless Thaltej-vs-Bodakdev loops create regret either way. Decide with process, then execute with focus.',
                    ]],
                ]),
        ];
    }
}
