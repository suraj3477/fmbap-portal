import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE

def build_presentation(output_files):
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # Executive Government Palette
    C_WHITE = RGBColor(255, 255, 255)
    C_NAVY_DARK = RGBColor(10, 25, 47)       # Deep Official Dark Navy
    C_NAVY = RGBColor(11, 37, 69)            # Deep Govt Navy #0B2545
    C_BLUE = RGBColor(15, 76, 159)           # Govt Primary Blue #0F4C9F
    C_LIGHT_BLUE = RGBColor(238, 244, 255)   # Light blue tint for cards
    C_DARK = RGBColor(15, 23, 42)            # Charcoal text #0F172A
    C_MUTED = RGBColor(51, 65, 85)           # Body slate #334155
    C_LINE = RGBColor(203, 213, 225)         # Soft borders #CBD5E1
    C_GOLD = RGBColor(180, 83, 9)            # Govt Deep Gold #B45309
    C_GREEN = RGBColor(21, 128, 61)          # Forest Green #15803D
    C_AMBER = RGBColor(194, 65, 12)          # Warm Amber #C2410C
    C_BADGE_BG = RGBColor(220, 252, 231)     # Light Mint Green for 'Monthly' badge
    C_BADGE_TXT = RGBColor(22, 101, 52)      # Deep Green for 'Monthly' text
    C_CARD_BG = RGBColor(248, 250, 252)      # Crisp card fill #F8FAFC

    # =========================================================================
    # SLIDE 1: EXECUTIVE ONE-PAGER SUMMARY (WITH THE 6 CORE CONCEPTS)
    # =========================================================================
    s1 = prs.slides.add_slide(blank_layout)

    # Background
    bg1 = s1.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
    bg1.fill.solid()
    bg1.fill.fore_color.rgb = C_WHITE
    bg1.line.fill.background()

    # Header
    tx_head = s1.shapes.add_textbox(Inches(0.8), Inches(0.40), Inches(11.73), Inches(0.85))
    tf_head = tx_head.text_frame
    tf_head.word_wrap = True
    tf_head.margin_left = tf_head.margin_top = tf_head.margin_right = tf_head.margin_bottom = 0

    p_org = tf_head.paragraphs[0]
    p_org.text = "GOVERNMENT OF INDIA  •  MINISTRY OF JAL SHAKTI  •  BRAHMAPUTRA BOARD"
    p_org.font.size = Pt(9.5)
    p_org.font.bold = True
    p_org.font.color.rgb = C_GOLD

    p_title = tf_head.add_paragraph()
    p_title.text = "FMBAP Digital Monitoring Portal — Executive Summary"
    p_title.font.size = Pt(21)
    p_title.font.bold = True
    p_title.font.color.rgb = C_NAVY
    p_title.space_before = Pt(2)

    p_sub = tf_head.add_paragraph()
    p_sub.text = "Flood Management & Border Areas Programme  |  System Architecture & Closed-Loop Governance Review"
    p_sub.font.size = Pt(10)
    p_sub.font.color.rgb = C_MUTED
    p_sub.space_before = Pt(2)

    # Main Body: 2 Columns
    col_w = Inches(5.72)
    col1_x = Inches(0.8)
    col2_x = Inches(6.81)
    y_body = Inches(1.48)
    h_body = Inches(5.30)

    # --- LEFT COLUMN: 1. INTRODUCTION & 2. CORE CONCEPTS ---
    tx_c1 = s1.shapes.add_textbox(col1_x, y_body, col_w, h_body)
    tf_c1 = tx_c1.text_frame
    tf_c1.word_wrap = True
    tf_c1.margin_left = tf_c1.margin_top = tf_c1.margin_right = tf_c1.margin_bottom = 0

    p_s1 = tf_c1.paragraphs[0]
    p_s1.text = "1. INTRODUCTION (Mandate & Scope)"
    p_s1.font.size = Pt(12)
    p_s1.font.bold = True
    p_s1.font.color.rgb = C_NAVY

    s1_items = [
        ("Institutional Mandate", "Central Sector Scheme monitoring 30 critical flood protection and anti-erosion works under Ministry of Jal Shakti across North-Eastern states."),
        ("Multi-Tier Funding Splits", "Fully configurable cost-sharing formulas: 90:10 (NE & Himalayan States), 60:40 / 50:50 (General States), and 100:0 (Border RMBA Projects)."),
        ("Operational Platform", "Hosted live on enterprise cloud at https://103.162.247.169/dashboard with all 30 baseline schemes fully populated.")
    ]
    for h, b in s1_items:
        p = tf_c1.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(9.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(3)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # Section 2: Core Concepts (Directly from User Screenshot)
    p_s2 = tf_c1.add_paragraph()
    p_s2.text = "2. CORE CONCEPTS (6 Functional Modules)"
    p_s2.font.size = Pt(12)
    p_s2.font.bold = True
    p_s2.font.color.rgb = C_BLUE
    p_s2.space_before = Pt(10)

    core_concepts_list = [
        ("Dashboard & GIS Map", "Executive command center with real-time financial KPIs and interactive spatial GIS mapping across Brahmaputra/Barak basins."),
        ("Schemes Catalogue", "Codified master registry of all 30 schemes with outlays, funding splits (90:10, 60:40, 100:0), tributaries, and GPS bounds."),
        ("Fund Release Claims", "Paperless submission and scrutiny of installment claims, tracking past releases and auto-generating Form GFR 12-A UCs."),
        ("Physical & Financial Progress (Monthly)", "Mandatory monthly progress returns logging component physical milestones, MB entries, and audited billing vouchers."),
        ("BB Site Monitoring", "On-ground technical audits by Board engineers with mandatory tamper-proof, GPS-tagged, and timestamped riverbank photos."),
        ("Executive MIS & Analytics", "Real-time 'Pending-at-Whom' file custody radar, SLA aging clocks, red alerts, and instant 1-click review dossiers.")
    ]
    for h, b in core_concepts_list:
        p = tf_c1.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(9.2)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(2.8)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # --- RIGHT COLUMN: 3. OPERATIONAL WORKFLOW & 4. STRATEGIC BENEFITS ---
    tx_c2 = s1.shapes.add_textbox(col2_x, y_body, col_w, h_body)
    tf_c2 = tx_c2.text_frame
    tf_c2.word_wrap = True
    tf_c2.margin_left = tf_c2.margin_top = tf_c2.margin_right = tf_c2.margin_bottom = 0

    p_s3 = tf_c2.paragraphs[0]
    p_s3.text = "3. OPERATIONAL WORKFLOW (5-Step Closed Loop)"
    p_s3.font.size = Pt(12)
    p_s3.font.bold = True
    p_s3.font.color.rgb = C_GREEN

    s3_items = [
        ("Step 1: State WRD Submission", "State department logs in, registers DPR parameters, enters contractor bill vouchers, and claims installment funds."),
        ("Step 2: Board Technical Audit", "Brahmaputra Board engineers inspect river site, verify physical spur/revetment work with GPS photos, and record recommendations."),
        ("Step 3: GFR 12-A Verification", "Portal auto-generates statutory Form GFR 12-A, cross-checking claimed money against verified physical milestone progress."),
        ("Step 4: Ministry Sanction & PFMS", "Ministry of Jal Shakti accords final financial sanction and logs PFMS transaction reference for direct fund credit."),
        ("Step 5: Executive MIS & Pendency Tracking", "System tracks file custody across desks and auto-compiles National MIS reports for Ministry and Parliamentary reviews.")
    ]
    for h, b in s3_items:
        p = tf_c2.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(9.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(3)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    p_s4 = tf_c2.add_paragraph()
    p_s4.text = "4. STRATEGIC BENEFITS (Governance Impact)"
    p_s4.font.size = Pt(12)
    p_s4.font.bold = True
    p_s4.font.color.rgb = C_AMBER
    p_s4.space_before = Pt(10)

    s4_items = [
        ("Zero Delay & 'Pending at Whom'", "Real-time desk ownership eliminates inter-departmental blame and prevents files getting lost in transit."),
        ("CAG Audit & Legal Defense", "Every disbursed rupee is backed by immutable digital records, contractor vouchers, and GPS-stamped photo logs."),
        ("Financial Discipline", "Enforces physical-before-financial release; automated formulas prevent arithmetic mismatches and over-allocation."),
        ("Real-Time Leadership Control", "Chairman, Member Technical, and Chief Engineers have complete command over all river basins, file aging, and MIS dossiers.")
    ]
    for h, b in s4_items:
        p = tf_c2.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(9.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(3)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # Footer
    y_foot = Inches(6.90)
    h_foot = Inches(0.40)
    w_foot = Inches(11.73)
    foot_bar = s1.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), y_foot, w_foot, h_foot)
    foot_bar.fill.solid()
    foot_bar.fill.fore_color.rgb = C_NAVY
    foot_bar.line.fill.background()

    tx_foot = s1.shapes.add_textbox(Inches(0.95), y_foot + Inches(0.06), w_foot - Inches(0.3), h_foot - Inches(0.12))
    tf_foot = tx_foot.text_frame
    tf_foot.word_wrap = True
    tf_foot.margin_left = tf_foot.margin_top = tf_foot.margin_right = tf_foot.margin_bottom = 0
    p_foot = tf_foot.paragraphs[0]
    p_foot.text = "PRODUCTION URL:  https://103.162.247.169/dashboard   |   24/7 AUTO-RECOVERY   |   ALL 30 SCHEMES PRELOADED"
    p_foot.font.size = Pt(9.5)
    p_foot.font.bold = True
    p_foot.font.color.rgb = C_WHITE
    p_foot.alignment = PP_ALIGN.CENTER

    # =========================================================================
    # SLIDE 2: DEDICATED VISUAL SLIDE — THE 6 CORE CONCEPTS (MATCHING USER IMAGE)
    # =========================================================================
    s2 = prs.slides.add_slide(blank_layout)

    bg2 = s2.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
    bg2.fill.solid()
    bg2.fill.fore_color.rgb = RGBColor(248, 250, 252) # Soft premium off-white/slate
    bg2.line.fill.background()

    # Header 2
    tx_head2 = s2.shapes.add_textbox(Inches(0.8), Inches(0.35), Inches(11.73), Inches(0.80))
    tf_head2 = tx_head2.text_frame
    tf_head2.word_wrap = True
    tf_head2.margin_left = tf_head2.margin_top = tf_head2.margin_right = tf_head2.margin_bottom = 0

    p_org2 = tf_head2.paragraphs[0]
    p_org2.text = "BRAHMAPUTRA BOARD  •  MINISTRY OF JAL SHAKTI  •  GOVERNMENT OF INDIA"
    p_org2.font.size = Pt(9.5)
    p_org2.font.bold = True
    p_org2.font.color.rgb = C_GOLD

    p_title2 = tf_head2.add_paragraph()
    p_title2.text = "FMBAP Digital Portal — 6 Core Functional Concepts"
    p_title2.font.size = Pt(21)
    p_title2.font.bold = True
    p_title2.font.color.rgb = C_NAVY
    p_title2.space_before = Pt(2)

    p_sub2 = tf_head2.add_paragraph()
    p_sub2.text = "System Navigation Architecture: End-to-End Scheme Scrutiny, Fund Release, and River Site Monitoring"
    p_sub2.font.size = Pt(10)
    p_sub2.font.color.rgb = C_MUTED
    p_sub2.space_before = Pt(2)

    # --- PORTAL NAVIGATION BAR REPLICA (Matches User Image Exactly) ---
    y_nav = Inches(1.28)
    h_nav = Inches(0.48)
    w_nav = Inches(11.73)
    x_nav = Inches(0.80)

    # Dark Navy Bar
    nav_bg = s2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x_nav, y_nav, w_nav, h_nav)
    nav_bg.fill.solid()
    nav_bg.fill.fore_color.rgb = RGBColor(11, 29, 58) # #0B1D3A Dark Blue Nav
    nav_bg.line.fill.background()

    # Navigation items text & badges
    # Tab 1: Dashboard & GIS Map
    # Tab 2: Schemes Catalogue
    # Tab 3: Fund Release Claims
    # Tab 4: Physical & Financial Progress [Monthly] (Active Tab style like screenshot)
    # Tab 5: BB Site Monitoring
    # Tab 6: Executive MIS & Analytics
    
    # Highlight active tab (Physical & Financial Progress) with white pill
    x_active = x_nav + Inches(5.42)
    w_active = Inches(2.78)
    active_pill = s2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x_active, y_nav + Inches(0.04), w_active, h_nav - Inches(0.08))
    active_pill.fill.solid()
    active_pill.fill.fore_color.rgb = C_WHITE
    active_pill.line.fill.background()

    # Monthly Badge inside active tab
    x_badge = x_active + Inches(2.05)
    w_badge = Inches(0.65)
    y_badge = y_nav + Inches(0.09)
    h_badge = Inches(0.30)
    badge_rect = s2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x_badge, y_badge, w_badge, h_badge)
    badge_rect.fill.solid()
    badge_rect.fill.fore_color.rgb = C_BADGE_BG
    badge_rect.line.fill.background()

    tx_b = s2.shapes.add_textbox(x_badge, y_badge + Inches(0.04), w_badge, h_badge)
    tf_b = tx_b.text_frame
    tf_b.margin_left = tf_b.margin_top = tf_b.margin_right = tf_b.margin_bottom = 0
    p_b = tf_b.paragraphs[0]
    p_b.text = "Monthly"
    p_b.font.size = Pt(8)
    p_b.font.bold = True
    p_b.font.color.rgb = C_BADGE_TXT
    p_b.alignment = PP_ALIGN.CENTER

    # Add text labels on the nav bar
    nav_labels = [
        (x_nav + Inches(0.15), Inches(1.50), "Dashboard & GIS Map", False),
        (x_nav + Inches(1.95), Inches(1.55), "Schemes Catalogue", False),
        (x_nav + Inches(3.70), Inches(1.55), "Fund Release Claims", False),
        (x_active + Inches(0.10), Inches(1.90), "Physical & Financial Progress", True),
        (x_nav + Inches(8.35), Inches(1.55), "BB Site Monitoring", False),
        (x_nav + Inches(10.05), Inches(1.55), "Executive MIS & Analytics", False),
    ]
    for x_pos, w_box, text, is_active in nav_labels:
        tx_item = s2.shapes.add_textbox(x_pos, y_nav + Inches(0.10), w_box, Inches(0.32))
        tf_item = tx_item.text_frame
        tf_item.margin_left = tf_item.margin_top = tf_item.margin_right = tf_item.margin_bottom = 0
        p_item = tf_item.paragraphs[0]
        p_item.text = text
        p_item.font.size = Pt(9.2)
        p_item.font.bold = True
        p_item.font.color.rgb = C_NAVY if is_active else RGBColor(190, 210, 240)
        p_item.alignment = PP_ALIGN.LEFT

    # --- 6 CORE CONCEPT CARDS (2 Rows x 3 Columns) ---
    card_w = Inches(3.75)
    card_h = Inches(2.32)
    gap_x = Inches(0.24)
    gap_y = Inches(0.18)

    col_coords = [Inches(0.80), Inches(0.80) + card_w + gap_x, Inches(0.80) + (card_w + gap_x) * 2]
    row_coords = [Inches(1.88), Inches(1.88) + card_h + gap_y]

    cards_data = [
        # Card 1: Dashboard & GIS Map
        {
            "num": "01",
            "title": "Dashboard & GIS Map",
            "badge": "COMMAND & SPATIAL RADAR",
            "badge_color": C_BLUE,
            "bullets": [
                ("Live Portfolio Metrics", "Instant view of ₹3,740.81 Cr outlays, ₹205.47 Cr central releases, and 66.2% execution across all 30 works."),
                ("Interactive River Basin Map", "Satellite & terrain GIS tracking across Teesta, Barak, Dhansiri, Lohit, Simsang & Siang reaches."),
                ("Status-Coded Geo Markers", "Pins color-coded by progress (Completed, In-Progress, Tendering) with 1-click scheme drilldowns.")
            ]
        },
        # Card 2: Schemes Catalogue
        {
            "num": "02",
            "title": "Schemes Catalogue",
            "badge": "MASTER STATUTORY REGISTRY",
            "badge_color": C_GOLD,
            "bullets": [
                ("Codified Scheme Master", "Unique IDs (SK-01, AS-12) permanently linking DPR outlays, state/central shares, and technical targets."),
                ("Configurable Funding Splits", "Automates multi-tier formulas: 90:10 (NE & Himalayan), 60:40 / 50:50 (General), and 100:0 (Border RMBA)."),
                ("Immutable Baseline Specs", "Records river reaches, executing agencies, sanctioned components, and GPS boundary coordinates.")
            ]
        },
        # Card 3: Fund Release Claims
        {
            "num": "03",
            "title": "Fund Release Claims",
            "badge": "PAPERLESS FINANCIAL SCRUTINY",
            "badge_color": C_GREEN,
            "bullets": [
                ("End-to-End Digital Workflow", "Replaces physical paper file movement with online submission and multi-tier scrutiny."),
                ("Form GFR 12-A Statutory Engine", "3-click automated Utilization Certificate creation; zero math errors in unspent balance calculations."),
                ("PFMS Credit Integration", "Logs MoJS financial sanctions and PFMS transaction references for direct central treasury credits.")
            ]
        },
        # Card 4: Physical & Financial Progress (Monthly)
        {
            "num": "04",
            "title": "Physical & Financial Progress",
            "badge": "MONTHLY VERIFICATION CYCLE",
            "badge_color": RGBColor(22, 101, 52),
            "bullets": [
                ("Mandatory Monthly Submissions", "Compulsory monthly reporting cycle enforced for all executing state divisions and contractors."),
                ("Component-Level Tracking", "Physical milestone tracking for boulder spurs, porcupines, geo-bag revetments, and embankments."),
                ("Financial Voucher Linking", "Direct reconciliation of MB (Measurement Book) entries and contractor billing against release allocations.")
            ]
        },
        # Card 5: BB Site Monitoring
        {
            "num": "05",
            "title": "BB Site Monitoring",
            "badge": "GROUND-TRUTH QUALITY AUDIT",
            "badge_color": C_AMBER,
            "bullets": [
                ("Field Engineering Audits", "Physical riverbank verification by Brahmaputra Board technical officers before any release."),
                ("Geo-Tagged Camera Logs", "Mandatory mobile river photos with device GPS latitude, longitude, and timestamps; blocks duplicate/stock photos."),
                ("Statutory CAG Defense", "Provides an unalterable, time-stamped digital photographic record safeguarding against audit scrutiny.")
            ]
        },
        # Card 6: Executive MIS & Analytics
        {
            "num": "06",
            "title": "Executive MIS & Analytics",
            "badge": "PENDENCY & DECISION SUPPORT",
            "badge_color": C_NAVY,
            "bullets": [
                ("Pending-at-Whom Desk Radar", "Real-time custody tracking showing exactly whose desk a file is on (State WRD / Board / MoJS)."),
                ("SLA Aging Timers & Red Alerts", "Automated escalation for files exceeding review thresholds; eliminates inter-departmental delays."),
                ("1-Click Executive Dossiers", "Generates consolidated state-wise progress briefs, expenditure registers, and dossiers for Ministry reviews.")
            ]
        }
    ]

    card_idx = 0
    for r in range(2):
        for c in range(3):
            data = cards_data[card_idx]
            card_x = col_coords[c]
            card_y = row_coords[r]

            # Card Container
            c_box = s2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, card_x, card_y, card_w, card_h)
            c_box.fill.solid()
            c_box.fill.fore_color.rgb = C_WHITE
            c_box.line.color.rgb = C_LINE
            c_box.line.width = Pt(1)

            # Top accent bar on each card
            top_bar = s2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, card_x, card_y, card_w, Inches(0.08))
            top_bar.fill.solid()
            top_bar.fill.fore_color.rgb = data["badge_color"]
            top_bar.line.fill.background()

            # Text content inside card
            tx_c = s2.shapes.add_textbox(card_x + Inches(0.16), card_y + Inches(0.14), card_w - Inches(0.32), card_h - Inches(0.20))
            tf_c = tx_c.text_frame
            tf_c.word_wrap = True
            tf_c.margin_left = tf_c.margin_top = tf_c.margin_right = tf_c.margin_bottom = 0

            # Card Title + Number
            p_ct = tf_c.paragraphs[0]
            r_num = p_ct.add_run()
            r_num.text = data["num"] + ". "
            r_num.font.bold = True
            r_num.font.size = Pt(11)
            r_num.font.color.rgb = data["badge_color"]

            r_title = p_ct.add_run()
            r_title.text = data["title"]
            r_title.font.bold = True
            r_title.font.size = Pt(11)
            r_title.font.color.rgb = C_DARK

            # Badge / Subtitle
            p_badge = tf_c.add_paragraph()
            p_badge.text = data["badge"]
            p_badge.font.size = Pt(7.5)
            p_badge.font.bold = True
            p_badge.font.color.rgb = data["badge_color"]
            p_badge.space_before = Pt(1)

            # Bullets
            for b_title, b_desc in data["bullets"]:
                p_b = tf_c.add_paragraph()
                p_b.text = "• " + b_title + ": "
                p_b.font.size = Pt(8.2)
                p_b.font.bold = True
                p_b.font.color.rgb = C_DARK
                p_b.space_before = Pt(3)

                r_b = p_b.add_run()
                r_b.text = b_desc
                r_b.font.bold = False
                r_b.font.color.rgb = C_MUTED

            card_idx += 1

    # Footer 2
    y_foot2 = Inches(6.92)
    foot_bar2 = s2.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), y_foot2, w_foot, Inches(0.38))
    foot_bar2.fill.solid()
    foot_bar2.fill.fore_color.rgb = C_NAVY
    foot_bar2.line.fill.background()

    tx_foot2 = s2.shapes.add_textbox(Inches(0.95), y_foot2 + Inches(0.06), w_foot - Inches(0.3), Inches(0.26))
    tf_foot2 = tx_foot2.text_frame
    tf_foot2.margin_left = tf_foot2.margin_top = tf_foot2.margin_right = tf_foot2.margin_bottom = 0
    p_foot2 = tf_foot2.paragraphs[0]
    p_foot2.text = "FMBAP MONITORING PORTAL LIVE AT:  https://103.162.247.169/dashboard   •   CLOSED-LOOP GOVERNANCE REPAIR"
    p_foot2.font.size = Pt(9.5)
    p_foot2.font.bold = True
    p_foot2.font.color.rgb = C_WHITE
    p_foot2.alignment = PP_ALIGN.CENTER

    # Save to all requested locations
    for out in output_files:
        try:
            prs.save(out)
            print(f"Presentation saved successfully at: {out}")
        except PermissionError:
            print(f"Skipping {out} (file locked/open in PowerPoint)")

if __name__ == "__main__":
    targets = [
        r"D:\fmbap-portal-main - Copy\FMBAP_Portal_Executive_Summary_Govt.pptx",
        r"C:\Users\HP\Downloads\FMBAP_Portal_Executive_Summary_Govt.pptx",
        r"D:\fmbap-portal-main - Copy\FMBAP_Portal_Executive_OnePager.pptx",
        r"D:\fmbap-portal-main - Copy\FMBAP_Portal_Single_Page_Summary.pptx"
    ]
    build_presentation(targets)
