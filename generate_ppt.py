import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.enum.shapes import MSO_SHAPE

def create_deck(output_path, logo_path=None):
    prs = Presentation()
    # 16:9 Widescreen
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # Color Palette
    C_NAVY_DARK = RGBColor(11, 37, 69)      # #0B2545 - Header & Dark Card
    C_BLUE_ROYAL = RGBColor(15, 76, 159)    # #0F4C9F - Govt Blue Primary
    C_BLUE_LIGHT = RGBColor(239, 246, 255)  # #EFF6FF - Light Accent BG
    C_BG_SLATE = RGBColor(248, 250, 252)    # #F8FAFC - Main Slide BG
    C_WHITE = RGBColor(255, 255, 255)       # Card BG
    C_BORDER = RGBColor(226, 232, 240)      # #E2E8F0 - Card Borders
    C_TEXT_DARK = RGBColor(15, 23, 42)      # #0F172A - Primary Text
    C_TEXT_MUTED = RGBColor(71, 85, 105)    # #475569 - Secondary Text
    C_AMBER = RGBColor(217, 119, 6)         # #D97706 - Financial & Warning
    C_EMERALD = RGBColor(5, 150, 105)       # #059669 - Positive & Completion
    C_INDIGO = RGBColor(67, 56, 202)        # #4338CA - Secondary Accent

    def set_slide_background(slide, color=C_BG_SLATE):
        bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
        bg.fill.solid()
        bg.fill.fore_color.rgb = color
        bg.line.fill.background()
        return bg

    def add_header(slide, title, category="FMBAP MONITORING PORTAL • BRAHMAPUTRA BOARD"):
        # Header banner
        header_box = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, Inches(1.15))
        header_box.fill.solid()
        header_box.fill.fore_color.rgb = C_NAVY_DARK
        header_box.line.fill.background()

        # Accent line
        accent = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, Inches(1.15), prs.slide_width, Inches(0.05))
        accent.fill.solid()
        accent.fill.fore_color.rgb = C_AMBER
        accent.line.fill.background()

        # Header Text
        tx = slide.shapes.add_textbox(Inches(0.8), Inches(0.18), Inches(10.5), Inches(0.85))
        tf = tx.text_frame
        tf.word_wrap = True
        tf.margin_left = tf.margin_top = tf.margin_right = tf.margin_bottom = 0

        p1 = tf.paragraphs[0]
        p1.text = category.upper()
        p1.font.size = Pt(10)
        p1.font.bold = True
        p1.font.color.rgb = C_AMBER

        p2 = tf.add_paragraph()
        p2.text = title
        p2.font.size = Pt(22)
        p2.font.bold = True
        p2.font.color.rgb = C_WHITE

        # Logo on right
        if logo_path and os.path.exists(logo_path):
            try:
                slide.shapes.add_picture(logo_path, Inches(11.8), Inches(0.2), height=Inches(0.75))
            except:
                pass

    def add_card(slide, x, y, w, h, bg=C_WHITE, border=C_BORDER):
        card = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x, y, w, h)
        card.fill.solid()
        card.fill.fore_color.rgb = bg
        if border:
            card.line.color.rgb = border
            card.line.width = Pt(1)
        else:
            card.line.fill.background()
        return card

    # ==========================================
    # SLIDE 1: Title Slide
    # ==========================================
    s1 = prs.slides.add_slide(blank_layout)
    set_slide_background(s1, C_NAVY_DARK)

    # Accent decorative bars
    top_bar = s1.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, Inches(0.12))
    top_bar.fill.solid()
    top_bar.fill.fore_color.rgb = C_AMBER
    top_bar.line.fill.background()

    if logo_path and os.path.exists(logo_path):
        try:
            s1.shapes.add_picture(logo_path, Inches(0.9), Inches(1.2), height=Inches(1.2))
        except:
            pass

    tbox = s1.shapes.add_textbox(Inches(0.9), Inches(2.6), Inches(11.5), Inches(3.2))
    tf1 = tbox.text_frame
    tf1.word_wrap = True

    p = tf1.paragraphs[0]
    p.text = "MINISTRY OF JAL SHAKTI • BRAHMAPUTRA BOARD"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_AMBER

    p = tf1.add_paragraph()
    p.text = "Flood Management & Border Areas Programme"
    p.font.size = Pt(36)
    p.font.bold = True
    p.font.color.rgb = C_WHITE

    p = tf1.add_paragraph()
    p.text = "FMBAP National Digital Monitoring Portal (2026-2027)"
    p.font.size = Pt(24)
    p.font.bold = True
    p.font.color.rgb = RGBColor(147, 197, 253)

    p = tf1.add_paragraph()
    p.text = "End-to-End System Review: Architecture, Statutory Compliance, GIS Tracking & Executive MIS"
    p.font.size = Pt(14)
    p.font.color.rgb = RGBColor(203, 213, 225)

    # Footer meta card
    meta_box = s1.shapes.add_textbox(Inches(0.9), Inches(6.0), Inches(11.5), Inches(0.9))
    tf_m = meta_box.text_frame
    p = tf_m.paragraphs[0]
    p.text = "Target Review: High-Level Steering Committee & Brahmaputra Board Leadership"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_WHITE

    p = tf_m.add_paragraph()
    p.text = "Deployment Status: Live on Public IP (103.162.247.169) | Built on Laravel 11 + Vue 3 + Inertia.js"
    p.font.size = Pt(10)
    p.font.color.rgb = RGBColor(148, 163, 184)

    # ==========================================
    # SLIDE 2: Executive Summary & Need
    # ==========================================
    s2 = prs.slides.add_slide(blank_layout)
    set_slide_background(s2)
    add_header(s2, "Executive Overview & Problem Context")

    # 3 Cards Layout
    card_w = Inches(3.64)
    card_h = Inches(5.6)
    spacing = Inches(0.4)
    start_x = Inches(0.8)
    top_y = Inches(1.45)

    # Card 1: Mandate
    add_card(s2, start_x, top_y, card_w, card_h)
    tx = s2.shapes.add_textbox(start_x + Inches(0.3), top_y + Inches(0.3), card_w - Inches(0.6), card_h - Inches(0.6))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "THE NATIONAL MANDATE"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = C_BLUE_ROYAL

    p = tf.add_paragraph()
    p.text = "\nProtecting Vulnerable River Basins"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_TEXT_DARK

    bullets = [
        "The Brahmaputra and Barak basins experience severe recurring annual floods, catastrophic bank erosion, and sediment loads.",
        "Central Sector Scheme FMBAP allocates substantial grants-in-aid to North-Eastern states (90% Central : 10% State funding ratio).",
        "Mandated strict central accountability and real-time validation across inter-state river systems."
    ]
    for b in bullets:
        p = tf.add_paragraph()
        p.text = "• " + b
        p.font.size = Pt(11)
        p.font.color.rgb = C_TEXT_MUTED

    # Card 2: Pain Points
    c2_x = start_x + card_w + spacing
    add_card(s2, c2_x, top_y, card_w, card_h)
    tx = s2.shapes.add_textbox(c2_x + Inches(0.3), top_y + Inches(0.3), card_w - Inches(0.6), card_h - Inches(0.6))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "OPERATIONAL CHALLENGES"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = C_AMBER

    p = tf.add_paragraph()
    p.text = "\nLegacy Bottlenecks"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_TEXT_DARK

    bullets = [
        "Manual Paper Files: Fund release requests and DPR documents took months to route between State WRD, Board, and Ministry.",
        "Verification Deficit: Difficult to independently confirm physical embankment completion before releasing funds.",
        "Statutory Delays: Misaligned GFR 12-A Utilization Certificates delayed subsequent Central Assistance installments.",
        "Information Silos: Leadership lacked unified real-time dashboard of basin-wide progress."
    ]
    for b in bullets:
        p = tf.add_paragraph()
        p.text = "• " + b
        p.font.size = Pt(11)
        p.font.color.rgb = C_TEXT_MUTED

    # Card 3: Digital Solution
    c3_x = c2_x + card_w + spacing
    add_card(s2, c3_x, top_y, card_w, card_h)
    tx = s2.shapes.add_textbox(c3_x + Inches(0.3), top_y + Inches(0.3), card_w - Inches(0.6), card_h - Inches(0.6))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "THE FMBAP PORTAL"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD

    p = tf.add_paragraph()
    p.text = "\nIntegrated Solution"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_TEXT_DARK

    bullets = [
        "Single-Window Digital Governance: Unified platform connecting State WRDs, Brahmaputra Board, and MoJS.",
        "Geotagged Field Audits: GPS latitude/longitude photo evidence mapped directly to ground schemes.",
        "Statutory GFR 12-A Engine: Auto-generated, pre-filled Utilization Certificates with digital audit trails.",
        "Executive MIS Radar: Live visibility into ₹3,740+ Cr outlay, pending files, and physical milestones."
    ]
    for b in bullets:
        p = tf.add_paragraph()
        p.text = "• " + b
        p.font.size = Pt(11)
        p.font.color.rgb = C_TEXT_MUTED

    # ==========================================
    # SLIDE 3: Live Portfolio Snapshot & KPIs
    # ==========================================
    s3 = prs.slides.add_slide(blank_layout)
    set_slide_background(s3)
    add_header(s3, "Live Portfolio Snapshot & Master KPIs")

    # 4 Metric Cards
    m_w = Inches(2.68)
    m_h = Inches(1.8)
    m_spacing = Inches(0.34)
    m_top = Inches(1.45)

    metrics_data = [
        ("TOTAL SANCTIONED OUTLAY", "₹3,740.81 Cr", "30 Scheme Baselines", C_BLUE_ROYAL),
        ("CENTRAL ASSISTANCE RELEASED", "₹205.47 Cr", "Verified Against GFR 12-A", C_EMERALD),
        ("EXECUTION PORTFOLIO", "17 / 30 Active", "17 Completed | 5 Ongoing | 8 Initial", C_INDIGO),
        ("AVG PHYSICAL PROGRESS", "66.2%", "On-Ground Weighted Metric", C_AMBER),
    ]

    for i, (title, val, sub, col) in enumerate(metrics_data):
        bx = Inches(0.8) + i * (m_w + m_spacing)
        add_card(s3, bx, m_top, m_w, m_h)
        tx = s3.shapes.add_textbox(bx + Inches(0.2), m_top + Inches(0.2), m_w - Inches(0.4), m_h - Inches(0.4))
        tf = tx.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = title
        p.font.size = Pt(9)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_MUTED

        p = tf.add_paragraph()
        p.text = val
        p.font.size = Pt(24)
        p.font.bold = True
        p.font.color.rgb = col

        p = tf.add_paragraph()
        p.text = sub
        p.font.size = Pt(9.5)
        p.font.color.rgb = C_TEXT_MUTED

    # Bottom Details: 2 columns
    bot_y = Inches(3.55)
    bot_h = Inches(3.5)
    col_w = Inches(5.65)

    # Left: Funding Model Breakdown
    add_card(s3, Inches(0.8), bot_y, col_w, bot_h)
    tx = s3.shapes.add_textbox(Inches(1.1), bot_y + Inches(0.3), col_w - Inches(0.6), bot_h - Inches(0.6))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "STATUTORY FUNDING RATIO & FINANCIAL DYNAMICS"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_BLUE_ROYAL

    items = [
        ("Special Category Funding Model", "90% Central Share funded by MoJS; 10% State Share funded by State WRD."),
        ("Installment Disbursal Policy", "1st Installment (Advance work) -> 2nd Installment strictly released upon 70%+ utilization of prior funds and physical verification."),
        ("Strict UC Compliance", "No subsequent grant release is permitted without verified GFR 12-A certification from the Brahmaputra Board.")
    ]
    for h_txt, b_txt in items:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # Right: Basin Coverage
    add_card(s3, Inches(6.85), bot_y, col_w, bot_h)
    tx = s3.shapes.add_textbox(Inches(7.15), bot_y + Inches(0.3), col_w - Inches(0.6), bot_h - Inches(0.6))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "CRITICAL RIVER BASIN FOOTPRINT"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_BLUE_ROYAL

    basins = [
        ("Brahmaputra Main & Tributaries", "Siang, Lohit, Dhansiri, Simsang, Ganol sub-basins across Assam, Arunachal & Meghalaya."),
        ("Barak River System", "Howrah, Tlawng, Jiri, Manipur River sub-basins covering Manipur, Mizoram & southern Assam."),
        ("Teesta Basin", "Strategic anti-erosion spurs and bank protection works in Sikkim & upper catchment zones.")
    ]
    for h_txt, b_txt in basins:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # ==========================================
    # SLIDE 4: Multi-Tier Governance & Roles
    # ==========================================
    s4 = prs.slides.add_slide(blank_layout)
    set_slide_background(s4)
    add_header(s4, "Multi-Tier Institutional Workflow & Role Hierarchy")

    roles = [
        ("STATE OFFICIALS", "State WRD (Assam, Meghalaya, etc.)", [
            "Draft & submit Detailed Project Proposals (DPR).",
            "Request installment releases against project milestones.",
            "Generate & certify Form GFR 12-A Utilization Certificates.",
            "Upload monthly physical & financial expenditure reports."
        ], C_EMERALD),
        ("BRAHMAPUTRA BOARD", "Regional Statutory Authority", [
            "Scrutinize technical proposals and hydraulic design parameters.",
            "Deploy Field Inspectors for mandatory on-ground inspections.",
            "Verify physical works via GPS geotagged photo evidence.",
            "Recommend or return proposals with statutory inspection reports."
        ], C_BLUE_ROYAL),
        ("MINISTRY (MoJS)", "Ministry of Jal Shakti, Central Govt", [
            "Final central sanction & financial approval authority.",
            "Sanction Central Assistance installments under FMBAP guidelines.",
            "Macro-monitoring of inter-state allocation vs expenditure.",
            "Policy compliance review and national progress dashboards."
        ], C_AMBER),
        ("SUPER ADMIN", "System & Security Governance", [
            "Official account approval (prevents unauthorized access).",
            "Scheme catalog master configuration & baseline imports.",
            "End-to-end statutory audit log tracking & NIC compliance.",
            "System security and cryptographic key management."
        ], C_NAVY_DARK),
    ]

    r_w = Inches(2.68)
    r_h = Inches(5.6)
    for i, (title, sub, tasks, col) in enumerate(roles):
        rx = Inches(0.8) + i * (r_w + Inches(0.34))
        add_card(s4, rx, Inches(1.45), r_w, r_h)
        tx = s4.shapes.add_textbox(rx + Inches(0.2), Inches(1.65), r_w - Inches(0.4), r_h - Inches(0.4))
        tf = tx.text_frame
        tf.word_wrap = True

        p = tf.paragraphs[0]
        p.text = title
        p.font.size = Pt(13)
        p.font.bold = True
        p.font.color.rgb = col

        p = tf.add_paragraph()
        p.text = sub
        p.font.size = Pt(10)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK

        p = tf.add_paragraph()
        p.text = ""

        for t in tasks:
            p = tf.add_paragraph()
            p.text = "• " + t
            p.font.size = Pt(10)
            p.font.color.rgb = C_TEXT_MUTED
            p.space_after = Pt(6)

    # ==========================================
    # SLIDE 5: Core Module: Scheme Master
    # ==========================================
    s5 = prs.slides.add_slide(blank_layout)
    set_slide_background(s5)
    add_header(s5, "Core Module: Scheme Master & DPR Management")

    col1_w = Inches(5.65)
    col2_w = Inches(5.65)

    add_card(s5, Inches(0.8), Inches(1.45), col1_w, Inches(5.6))
    tx = s5.shapes.add_textbox(Inches(1.1), Inches(1.75), col1_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "COMPREHENSIVE PROJECT REPOSITORY"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_BLUE_ROYAL

    features = [
        ("Unique Scheme Codification", "Every scheme receives an immutable scheme code (e.g. SK-01, AS-12) linking DPRs, expenditures, and inspections."),
        ("GIS Spatial Tagging", "Coordinates (Latitude/Longitude), district, river basin, and village demarcated upon creation."),
        ("Dynamic Financial Computation", "Real-time calculation of Central Share (90%) and State Share (10%) based on total sanctioned cost in ₹ Crores."),
        ("Bulk Excel Ingestion Engine", "Engineers can download standard Government templates, fill hundreds of scheme parameters, and ingest with validation.")
    ]
    for h_txt, b_txt in features:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # Right Column: Scheme lifecycle
    add_card(s5, Inches(6.85), Inches(1.45), col2_w, Inches(5.6))
    tx = s5.shapes.add_textbox(Inches(7.15), Inches(1.75), col2_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "SCHEME LIFECYCLE TRACKER"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_BLUE_ROYAL

    stages = [
        ("Stage 1: DPR Inception & Appraisal", "State WRD submits Detailed Project Report. Initial technical vetting conducted by Brahmaputra Board engineers."),
        ("Stage 2: Administrative Approval", "Technical sanction issued; baseline funding ratio and physical milestones recorded in the database."),
        ("Stage 3: Milestone-Based Execution", "Tendering, work order award, and on-site construction tracking with monthly progress reporting."),
        ("Stage 4: Post-Flood Impact & Completion", "Final geo-verification of completed embankments, boulder spurs, and sluice gates before project closure.")
    ]
    for h_txt, b_txt in stages:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # ==========================================
    # SLIDE 6: Core Module: GFR 12-A Fund Releases
    # ==========================================
    s6 = prs.slides.add_slide(blank_layout)
    set_slide_background(s6)
    add_header(s6, "Core Module: Fund Requisitions & Statutory GFR 12-A Engine")

    add_card(s6, Inches(0.8), Inches(1.45), col1_w, Inches(5.6))
    tx = s6.shapes.add_textbox(Inches(1.1), Inches(1.75), col1_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "DIGITAL GFR 12-A COMPLIANCE"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD

    gfr_pts = [
        ("Rule 238(1) GFR 2017 Adherence", "Direct compliance with Central Government rules governing grants-in-aid to states."),
        ("Automated Pre-Fill Engine", "Portal automatically computes prior released funds, unspent balances, interest accrued, and current claim."),
        ("Official PDF Generation", "Generates standardized Form GFR 12-A containing digital record references and audit metadata in one click."),
        ("Claim Attachment", "Allows officers to immediately bundle the generated UC with contractor vouchers into the claim dossier.")
    ]
    for h_txt, b_txt in gfr_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    add_card(s6, Inches(6.85), Inches(1.45), col2_w, Inches(5.6))
    tx = s6.shapes.add_textbox(Inches(7.15), Inches(1.75), col2_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "DISBURSAL PIPELINE & AUDIT TRAIL"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD

    pipeline = [
        ("Step 1: State Requisition", "State WRD submits claim with certified expenditure vouchers and GFR 12-A."),
        ("Step 2: Brahmaputra Board Audit", "BB Finance & Technical cell reviews vouchers against field inspection reports."),
        ("Step 3: Board Forwarding to MoJS", "Brahmaputra Board forwards recommended release amount with inspection concurrence."),
        ("Step 4: Ministry Sanction & Release", "MoJS issues financial sanction order and enters PFMS release reference.")
    ]
    for h_txt, b_txt in pipeline:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # ==========================================
    # SLIDE 7: Core Module: Geotagged Field Inspection
    # ==========================================
    s7 = prs.slides.add_slide(blank_layout)
    set_slide_background(s7)
    add_header(s7, "Core Module: Geotagged Field Audits & Verification")

    add_card(s7, Inches(0.8), Inches(1.45), col1_w, Inches(5.6))
    tx = s7.shapes.add_textbox(Inches(1.1), Inches(1.75), col1_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "PHYSICAL TRUTH VERIFICATION"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_BLUE_ROYAL

    points = [
        ("Mandatory GPS Coordinates", "Every on-site photo requires latitude, longitude, and timestamp metadata to prove inspection on the actual river bank."),
        ("Interactive Inspection Gallery", "Supervising officials can click on inspection photos to view exact GPS locations plotted on satellite maps."),
        ("Physical vs Financial Gap", "System automatically flags projects where financial claims exceed verified physical embankment progress."),
        ("Standardized Quality Checklists", "Inspectors grade structural integrity, stone pitching quality, geo-bag stability, and river scour depth.")
    ]
    for h_txt, b_txt in points:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    add_card(s7, Inches(6.85), Inches(1.45), col2_w, Inches(5.6))
    tx = s7.shapes.add_textbox(Inches(7.15), Inches(1.75), col2_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "INSPECTION PROTOCOL & REPORTING"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_BLUE_ROYAL

    proto = [
        ("Pre-Monsoon Preparedness Audit", "Verifies flood readiness of embankments and closing of critical river breaches before flood season."),
        ("Intra-Monsoon Emergency Reporting", "Rapid logging of flood damage, revetment settling, or overflow requiring immediate central intervention."),
        ("Post-Monsoon Handover Report", "Final certification confirming works survived high-flood levels with physical % completion."),
        ("PDF Dossier Generation", "Compiles inspection notes, photos, and officer remarks into an official exportable PDF for ministry records.")
    ]
    for h_txt, b_txt in proto:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # ==========================================
    # SLIDE 8: Core Module: GIS Basin Map
    # ==========================================
    s8 = prs.slides.add_slide(blank_layout)
    set_slide_background(s8)
    add_header(s8, "Spatial Intelligence: Interactive GIS River Basin Map")

    add_card(s8, Inches(0.8), Inches(1.45), col1_w, Inches(5.6))
    tx = s8.shapes.add_textbox(Inches(1.1), Inches(1.75), col1_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "SPATIAL MAPPING ENGINE"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_INDIGO

    gis_f = [
        ("Leaflet & OpenStreetMap Integration", "Zero-license, lightweight GIS engine running directly in the browser with high-framerate rendering."),
        ("Color-Coded Status Markers", "Green (Completed), Amber (Ongoing), Blue (Initial Phase) markers plotted at exact geographic coordinates."),
        ("Multi-Layer Satellite & Terrain Views", "Switch seamlessly between topographic river terrain, satellite imagery, and high-contrast street maps."),
        ("One-Click Project Navigation", "Clicking any scheme in the side list flies the camera to the exact river bend with detailed popup metrics.")
    ]
    for h_txt, b_txt in gis_f:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    add_card(s8, Inches(6.85), Inches(1.45), col2_w, Inches(5.6))
    tx = s8.shapes.add_textbox(Inches(7.15), Inches(1.75), col2_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "BASIN-LEVEL FILTERING & INTELLIGENCE"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_INDIGO

    basin_int = [
        ("River Basin Facets", "Instantly filter schemes across Teesta, Barak, Dhansiri, Lohit, Simsang, and Siang catchments."),
        ("State & Outlay Scoping", "Isolate all works in Assam, Meghalaya, or Sikkim to evaluate geographic balance of central funding."),
        ("Flood Hotspot Identification", "Visual clustering highlights river reaches with dense anti-erosion works vs unshielded stretches."),
        ("No App / Plugin Required", "Runs natively in browser across desktops, tablets, and field mobile devices.")
    ]
    for h_txt, b_txt in basin_int:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # ==========================================
    # SLIDE 9: Core Module: Executive MIS Radar
    # ==========================================
    s9 = prs.slides.add_slide(blank_layout)
    set_slide_background(s9)
    add_header(s9, "Executive MIS Cockpit & Pendency Radar")

    add_card(s9, Inches(0.8), Inches(1.45), col1_w, Inches(5.6))
    tx = s9.shapes.add_textbox(Inches(1.1), Inches(1.75), col1_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "DESK-LEVEL PENDENCY TRACKER"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_AMBER

    mis_pts = [
        ("Accountability by Desk", "Identifies exactly where every claim is currently stalled (Pending at State WRD, Brahmaputra Board, or MoJS)."),
        ("Aging & Days Elapsed", "Highlights claims pending over 15, 30, or 60 days to proactively eliminate bureaucratic delays."),
        ("Actionable Resolution Cards", "Displays exact pending action required (e.g. 'Awaiting Form GFR 12-A', 'Awaiting Field Inspection')."),
        ("Preventing Project Stalls", "Enables Chairman & Joint Secretary to intervene before works miss the pre-monsoon dry season.")
    ]
    for h_txt, b_txt in mis_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    add_card(s9, Inches(6.85), Inches(1.45), col2_w, Inches(5.6))
    tx = s9.shapes.add_textbox(Inches(7.15), Inches(1.75), col2_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "REPORTING & REVIEW TOOLS"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_AMBER

    rep_pts = [
        ("One-Click Executive Excel Export", "Full statutory dataset exported into formatted Excel spreadsheets for Parliamentary & CAG review."),
        ("Executive PDF Cockpit Report", "Standardized summary PDF with KPI tables and completion radar ready for Board meetings."),
        ("Inter-State Comparative Barometers", "Evaluates spending pace: compare fund utilization in Assam vs Meghalaya vs Sikkim."),
        ("Physical-Financial Gap Analysis", "Discrepancy alarms if financial claims outpace audited on-ground construction.")
    ]
    for h_txt, b_txt in rep_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # ==========================================
    # SLIDE 10: Technical Architecture
    # ==========================================
    s10 = prs.slides.add_slide(blank_layout)
    set_slide_background(s10)
    add_header(s10, "Modern Full-Stack Technical Architecture")

    t_w = Inches(3.64)
    t_h = Inches(5.6)

    # Col 1: Backend
    add_card(s10, start_x, top_y, t_w, t_h)
    tx = s10.shapes.add_textbox(start_x + Inches(0.25), top_y + Inches(0.25), t_w - Inches(0.5), t_h - Inches(0.5))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "BACKEND CORE"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = C_BLUE_ROYAL
    p = tf.add_paragraph()
    p.text = "\nLaravel 11 / PHP 8.3"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_TEXT_DARK

    b_pts = [
        "Eloquent ORM: Clean relational models for Schemes, Payment Requests, and Inspections.",
        "Statutory GFR Service: Dedicated Gfr12aService generating legal PDFs via DomPDF.",
        "Role-Based Middleware: Strict boundary policies (EnsureAccountIsApproved, CanViewMis).",
        "Maatwebsite Excel: High-speed streaming export/import engine."
    ]
    for b in b_pts:
        p = tf.add_paragraph()
        p.text = "• " + b
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # Col 2: Frontend
    add_card(s10, c2_x, top_y, t_w, t_h)
    tx = s10.shapes.add_textbox(c2_x + Inches(0.25), top_y + Inches(0.25), t_w - Inches(0.5), t_h - Inches(0.5))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "FRONTEND CLIENT"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD
    p = tf.add_paragraph()
    p.text = "\nVue 3 + Inertia.js"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_TEXT_DARK

    f_pts = [
        "Inertia.js Protocol: Single-Page Application (SPA) speed without REST API overhead.",
        "Vue 3 Composition API: High-performance reactive UI components.",
        "Tailwind CSS Design System: Official Indian Government / Parichay visual guidelines (#0F4C9F).",
        "Vite Bundling: Optimized tree-shaken production bundles with prefetching."
    ]
    for b in f_pts:
        p = tf.add_paragraph()
        p.text = "• " + b
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # Col 3: Database & Security
    add_card(s10, c3_x, top_y, t_w, t_h)
    tx = s10.shapes.add_textbox(c3_x + Inches(0.25), top_y + Inches(0.25), t_w - Inches(0.5), t_h - Inches(0.5))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "DATA & SECURITY"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = C_AMBER
    p = tf.add_paragraph()
    p.text = "\nEnterprise Hardening"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_TEXT_DARK

    s_pts = [
        "Database: Production SQLite/MySQL schema with strict foreign keys and indexed spatial queries.",
        "CSRF & XSS Shield: Sanctum token validation and automated input sanitization.",
        "Approval Gatekeeper: New accounts cannot log in until Super Admin manually verifies officer credentials.",
        "Audit Logging: Comprehensive event observers logging state changes."
    ]
    for b in s_pts:
        p = tf.add_paragraph()
        p.text = "• " + b
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # ==========================================
    # SLIDE 11: Deployment & Cloud Infrastructure
    # ==========================================
    s11 = prs.slides.add_slide(blank_layout)
    set_slide_background(s11)
    add_header(s11, "Production Deployment & High-Availability Infrastructure")

    add_card(s11, Inches(0.8), Inches(1.45), col1_w, Inches(5.6))
    tx = s11.shapes.add_textbox(Inches(1.1), Inches(1.75), col1_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "LIVE CLOUD SPECIFICATIONS"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_BLUE_ROYAL

    vm_specs = [
        ("VM Designation", "bra20251028090919_Brahmaputra_Ubuntu24_VM3"),
        ("Compute Resources", "8 vCPU | 16 GB High-Speed RAM"),
        ("Storage Architecture", "30 GB OS Disk + 70 GB Dedicated Data & Document Disk"),
        ("Public Endpoint", "https://103.162.247.169 (Secured over Port 443 HTTPS)"),
        ("Internal DC IP", "10.103.79.44 (Reachable via Juniper Secure Connect)")
    ]
    for h_txt, b_txt in vm_specs:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    add_card(s11, Inches(6.85), Inches(1.45), col2_w, Inches(5.6))
    tx = s11.shapes.add_textbox(Inches(7.15), Inches(1.75), col2_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "HIGH-AVAILABILITY SYSTEMD CONFIGURATION"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD

    ha_pts = [
        ("Systemd Auto-Start on Boot", "Nginx and PHP 8.3-FPM enabled across multi-user targets; system self-starts immediately upon VM reboot."),
        ("Automatic Watchdog (Self-Healing)", "Configured systemd override with Restart=always and RestartSec=3s. If the web server ever terminates, Linux automatically resurrects it in 3 seconds."),
        ("Production Asset Delivery", "Static assets pre-compiled via Vite; served directly by Nginx with high-speed gzip compression."),
        ("Automated CI/CD Pipeline", "Single-command git synchronization: pull, build, optimize, and clear caches seamlessly.")
    ]
    for h_txt, b_txt in ha_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    # ==========================================
    # SLIDE 12: Roadmap & Strategic Impact
    # ==========================================
    s12 = prs.slides.add_slide(blank_layout)
    set_slide_background(s12)
    add_header(s12, "Strategic Impact & Future Roadmap")

    add_card(s12, Inches(0.8), Inches(1.45), col1_w, Inches(5.6))
    tx = s12.shapes.add_textbox(Inches(1.1), Inches(1.75), col1_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "TRANSFORMATIVE GOVERNANCE OUTCOMES"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD

    impacts = [
        ("Zero Physical Paper Stalls", "File movement reduced from 60+ days to under 48 hours with digital workflow and notifications."),
        ("100% Verified Fund Release", "Eliminated uninspected financial disbursals through GPS-backed photo evidence."),
        ("Transparent Center-State Relations", "State WRDs and Central Ministry view the exact same live ledger, eliminating data disputes."),
        ("Disaster Preparedness", "Basin GIS mapping ensures emergency anti-erosion works are identified and completed before peak monsoon.")
    ]
    for h_txt, b_txt in impacts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    add_card(s12, Inches(6.85), Inches(1.45), col2_w, Inches(5.6))
    tx = s12.shapes.add_textbox(Inches(7.15), Inches(1.75), col2_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "PHASE 2 ROADMAP (2026-2027)"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_BLUE_ROYAL

    roadmap = [
        ("Official NIC Domain & SSL Certificate", "Mapping fmbap.brahmaputraboard.gov.in with official NIC SSL certificate."),
        ("IoT River Sensor Telemetry", "Direct integration with CWC telemetry sensors for real-time water level and breach alert overlays."),
        ("Mobile Field Application (PWA / Android)", "Offline-capable geotagged photo capture with automatic synchronization upon cellular connectivity."),
        ("AI-Powered Satellite Scour Detection", "Automated satellite analysis to evaluate post-flood embankment erosion.")
    ]
    for h_txt, b_txt in roadmap:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_TEXT_MUTED

    prs.save(output_path)
    print(f"Presentation successfully saved to: {output_path}")

if __name__ == "__main__":
    logo = r"d:\fmbap-portal-main - Copy\public\logo.png"
    out = r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Review_Presentation.pptx"
    create_deck(out, logo)
