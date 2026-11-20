import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE

def build_presentation(output_file):
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # Color Palette - Professional Govt Blue & Clean Cards
    C_NAVY = RGBColor(11, 37, 69)         # #0B2545 - Deep Official Navy
    C_BLUE = RGBColor(15, 76, 159)        # #0F4C9F - Govt Primary Blue
    C_LIGHT_BG = RGBColor(245, 247, 250)  # #F5F7FA - Clean soft slate
    C_WHITE = RGBColor(255, 255, 255)
    C_CARD_BORDER = RGBColor(218, 225, 233)
    C_DARK = RGBColor(15, 23, 42)
    C_MUTED = RGBColor(71, 85, 105)
    C_GREEN = RGBColor(16, 124, 65)       # Positive / Complete
    C_AMBER = RGBColor(217, 119, 6)       # Financial / Action
    C_GOLD = RGBColor(245, 158, 11)       # Accent gold

    # Image Paths
    img_logo = r"d:\fmbap-portal-main - Copy\public\logo.png"
    img_login = r"d:\fmbap-portal-main - Copy\screenshots\01_login_screen.jpg"
    img_dash = r"d:\fmbap-portal-main - Copy\screenshots\02_full_dashboard.jpg"
    img_kpi = r"d:\fmbap-portal-main - Copy\screenshots\04_kpi_metrics.jpg"
    img_map = r"d:\fmbap-portal-main - Copy\screenshots\05_gis_basin_map.jpg"

    def set_bg(slide, color=C_LIGHT_BG):
        bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
        bg.fill.solid()
        bg.fill.fore_color.rgb = color
        bg.line.fill.background()
        return bg

    def add_header(slide, title, subtitle="BRAHMAPUTRA BOARD • FMBAP MONITORING PORTAL REVIEW"):
        # Header banner
        h_box = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, Inches(1.15))
        h_box.fill.solid()
        h_box.fill.fore_color.rgb = C_NAVY
        h_box.line.fill.background()

        # Gold line
        line = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, Inches(1.15), prs.slide_width, Inches(0.06))
        line.fill.solid()
        line.fill.fore_color.rgb = C_GOLD
        line.line.fill.background()

        # Text
        tx = slide.shapes.add_textbox(Inches(0.8), Inches(0.18), Inches(10.5), Inches(0.85))
        tf = tx.text_frame
        tf.word_wrap = True
        tf.margin_left = tf.margin_top = tf.margin_right = tf.margin_bottom = 0

        p1 = tf.paragraphs[0]
        p1.text = subtitle.upper()
        p1.font.size = Pt(10)
        p1.font.bold = True
        p1.font.color.rgb = C_GOLD

        p2 = tf.add_paragraph()
        p2.text = title
        p2.font.size = Pt(22)
        p2.font.bold = True
        p2.font.color.rgb = C_WHITE

        # Logo on top right
        if os.path.exists(img_logo):
            try:
                slide.shapes.add_picture(img_logo, Inches(12.0), Inches(0.2), height=Inches(0.75))
            except:
                pass

    def add_card(slide, x, y, w, h, bg=C_WHITE, border=C_CARD_BORDER):
        card = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x, y, w, h)
        card.fill.solid()
        card.fill.fore_color.rgb = bg
        if border:
            card.line.color.rgb = border
            card.line.width = Pt(1.2)
        else:
            card.line.fill.background()
        return card

    # ==========================================
    # SLIDE 1: Title Slide (Simple & Clean)
    # ==========================================
    s1 = prs.slides.add_slide(blank_layout)
    set_bg(s1, C_NAVY)

    # Gold top accent
    top_bar = s1.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, Inches(0.15))
    top_bar.fill.solid()
    top_bar.fill.fore_color.rgb = C_GOLD
    top_bar.line.fill.background()

    # Logo
    if os.path.exists(img_logo):
        try:
            s1.shapes.add_picture(img_logo, Inches(1.0), Inches(1.3), height=Inches(1.2))
        except:
            pass

    tx = s1.shapes.add_textbox(Inches(1.0), Inches(2.7), Inches(11.3), Inches(3.2))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "GOVERNMENT OF INDIA • MINISTRY OF JAL SHAKTI"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_GOLD

    p = tf.add_paragraph()
    p.text = "FMBAP Digital Monitoring Portal"
    p.font.size = Pt(38)
    p.font.bold = True
    p.font.color.rgb = C_WHITE

    p = tf.add_paragraph()
    p.text = "Flood Management & Border Areas Programme — Brahmaputra Board"
    p.font.size = Pt(20)
    p.font.color.rgb = RGBColor(147, 197, 253)

    p = tf.add_paragraph()
    p.text = "A Simple, Transparent System to Track River Schemes, Field Photos, and Fund Releases"
    p.font.size = Pt(14)
    p.font.color.rgb = RGBColor(203, 213, 225)

    # Footer note card
    tx_f = s1.shapes.add_textbox(Inches(1.0), Inches(6.0), Inches(11.3), Inches(0.8))
    tf_f = tx_f.text_frame
    p = tf_f.paragraphs[0]
    p.text = "Official Review Presentation for Board Members & Senior Officials"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_WHITE
    p = tf_f.add_paragraph()
    p.text = "Live Portal URL: https://103.162.247.169 | Public Cloud Deployment"
    p.font.size = Pt(10.5)
    p.font.color.rgb = RGBColor(148, 163, 184)

    # ==========================================
    # SLIDE 2: Why We Built This Portal (Simple Problem & Solution)
    # ==========================================
    s2 = prs.slides.add_slide(blank_layout)
    set_bg(s2)
    add_header(s2, "Why We Built This Portal: The Need for Change")

    w_half = Inches(5.6)
    h_box = Inches(5.6)
    y_box = Inches(1.45)

    # Left: The Old Way
    add_card(s2, Inches(0.8), y_box, w_half, h_box)
    tx = s2.shapes.add_textbox(Inches(1.1), y_box + Inches(0.3), w_half - Inches(0.6), h_box - Inches(0.6))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "❌ THE OLD WAY (PAPER & DELAYS)"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = RGBColor(185, 28, 28)

    old_points = [
        ("Slow File Movement", "Files took 60 to 90 days moving on paper between State WRD offices, Guwahati, and New Delhi."),
        ("No Photo Proof", "Difficult for senior officials to verify if river embankments and spurs were actually built before releasing money."),
        ("Pending UC Certificates", "Utilization Certificates (GFR 12-A) were often delayed or had calculation errors, stopping next funds."),
        ("No Single View", "Senior leaders did not have one single screen to see all schemes across all North Eastern states.")
    ]
    for title, desc in old_points:
        p = tf.add_paragraph()
        p.text = "\n• " + title
        p.font.size = Pt(12)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "  " + desc
        p.font.size = Pt(11)
        p.font.color.rgb = C_MUTED

    # Right: The New Way
    add_card(s2, Inches(6.9), y_box, w_half, h_box)
    tx = s2.shapes.add_textbox(Inches(7.2), y_box + Inches(0.3), w_half - Inches(0.6), h_box - Inches(0.6))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "✅ THE NEW WAY (THE FMBAP PORTAL)"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_GREEN

    new_points = [
        ("Online In Minutes", "State proposals, bills, and inspection reports are submitted and approved digitally in 24 to 48 hours."),
        ("Photos with GPS Location", "Inspectors take site photos on their mobile phones with exact latitude, longitude, and date stamped."),
        ("1-Click GFR 12-A Certificates", "System automatically calculates past balances and generates certified Government UC PDFs."),
        ("One Live National Dashboard", "Chairman, Chief Engineers, and Ministry officials can see all 30 schemes on an interactive river map.")
    ]
    for title, desc in new_points:
        p = tf.add_paragraph()
        p.text = "\n• " + title
        p.font.size = Pt(12)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "  " + desc
        p.font.size = Pt(11)
        p.font.color.rgb = C_MUTED

    # ==========================================
    # SLIDE 3: Live Snapshot Today (With Screenshot)
    # ==========================================
    s3 = prs.slides.add_slide(blank_layout)
    set_bg(s3)
    add_header(s3, "Live Numbers Today: Portal Financial & Physical Status")

    # Top KPI screenshot
    if os.path.exists(img_kpi):
        add_card(s3, Inches(0.8), Inches(1.4), Inches(11.73), Inches(2.5), bg=C_WHITE)
        try:
            s3.shapes.add_picture(img_kpi, Inches(1.0), Inches(1.5), width=Inches(11.33))
        except:
            pass

    # Bottom Explanation Cards: 4 simple takeaways
    c_w = Inches(2.7)
    c_h = Inches(2.8)
    gap = Inches(0.31)
    y_kpi_text = Inches(4.2)

    kpi_cards = [
        ("TOTAL SANCTIONED", "₹3,740.81 Cr", "30 Scheme Baselines", "Central 90% : State 10% special funding ratio approved across North-Eastern states.", C_BLUE),
        ("FUNDS RELEASED", "₹205.47 Cr", "Verified Against UCs", "All disbursed funds are strictly matched against on-ground verified expenditure.", C_GREEN),
        ("EXECUTION STATUS", "17 / 30 Schemes", "High Completion", "17 schemes completed, 5 under active construction, 8 in initial tendering.", C_NAVY),
        ("AVERAGE PROGRESS", "66.2%", "Physical Completion", "Calculated directly from field inspection reports of embankments & spurs.", C_AMBER)
    ]

    for i, (title, main_val, sub_val, note, col) in enumerate(kpi_cards):
        cx = Inches(0.8) + i * (c_w + gap)
        add_card(s3, cx, y_kpi_text, c_w, c_h)
        tx = s3.shapes.add_textbox(cx + Inches(0.18), y_kpi_text + Inches(0.2), c_w - Inches(0.36), c_h - Inches(0.4))
        tf = tx.text_frame
        tf.word_wrap = True

        p = tf.paragraphs[0]
        p.text = title
        p.font.size = Pt(9.5)
        p.font.bold = True
        p.font.color.rgb = C_MUTED

        p = tf.add_paragraph()
        p.text = main_val
        p.font.size = Pt(20)
        p.font.bold = True
        p.font.color.rgb = col

        p = tf.add_paragraph()
        p.text = sub_val
        p.font.size = Pt(10)
        p.font.bold = True
        p.font.color.rgb = C_DARK

        p = tf.add_paragraph()
        p.text = note
        p.font.size = Pt(9.5)
        p.font.color.rgb = C_MUTED

    # ==========================================
    # SLIDE 4: How the Portal Works (Step by Step)
    # ==========================================
    s4 = prs.slides.add_slide(blank_layout)
    set_bg(s4)
    add_header(s4, "How It Works: Simple 4-Step Online Workflow")

    step_w = Inches(2.7)
    step_h = Inches(5.6)

    steps = [
        ("STEP 1", "State WRD Submits", [
            "State officers (Assam, Meghalaya, Sikkim, etc.) log in.",
            "Upload project proposal (DPR) with estimated cost.",
            "System calculates 90% Central Share and 10% State Share automatically.",
            "No paper courier needed."
        ], C_BLUE),
        ("STEP 2", "Board Field Audit", [
            "Brahmaputra Board engineers scrutinize hydraulic drawings.",
            "Inspectors visit site with mobile phones.",
            "Take GPS-tagged photos of embankments and spurs.",
            "Certify exact physical work completed."
        ], C_AMBER),
        ("STEP 3", "GFR 12-A Certification", [
            "State requests next fund installment.",
            "Portal auto-fills the statutory GFR 12-A Utilization Certificate.",
            "Verifies past bills and balance money.",
            "Board recommends fund release."
        ], C_GREEN),
        ("STEP 4", "Ministry Sanctions", [
            "Ministry of Jal Shakti (MoJS) reviews the verified dossier.",
            "Approves Central Assistance installment with one click.",
            "PFMS release reference entered.",
            "State WRD receives fund alert."
        ], C_NAVY),
    ]

    for i, (tag, heading, points, col) in enumerate(steps):
        sx = Inches(0.8) + i * (step_w + gap)
        add_card(s4, sx, Inches(1.45), step_w, step_h)
        tx = s4.shapes.add_textbox(sx + Inches(0.2), Inches(1.65), step_w - Inches(0.4), step_h - Inches(0.4))
        tf = tx.text_frame
        tf.word_wrap = True

        p = tf.paragraphs[0]
        p.text = tag
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = col

        p = tf.add_paragraph()
        p.text = heading
        p.font.size = Pt(15)
        p.font.bold = True
        p.font.color.rgb = C_DARK

        p = tf.add_paragraph()
        p.text = ""

        for pt in points:
            p = tf.add_paragraph()
            p.text = "• " + pt
            p.font.size = Pt(10.5)
            p.font.color.rgb = C_MUTED
            p.space_after = Pt(8)

    # ==========================================
    # SLIDE 5: Screenshot: Officer Login & Roles
    # ==========================================
    s5 = prs.slides.add_slide(blank_layout)
    set_bg(s5)
    add_header(s5, "Screenshot: Secure Departmental Login Portal")

    # Left: Explanation
    w_left = Inches(4.5)
    add_card(s5, Inches(0.8), Inches(1.45), w_left, Inches(5.6))
    tx = s5.shapes.add_textbox(Inches(1.0), Inches(1.75), w_left - Inches(0.4), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "GOVERNMENT STANDARD LOGIN"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    p = tf.add_paragraph()
    p.text = "Role-Based Officer Access"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    pts = [
        "National Emblem & Parichay Style: Built following official Government of India web standards.",
        "Strict Role Protection: Every officer sees ONLY what they are authorized to see:",
        "  - Super Admin: Approves all new accounts.",
        "  - Board Officials: Can inspect, review, and monitor.",
        "  - State Officials: Can only submit for their state.",
        "  - Ministry (MoJS): Sanctions funds & views all.",
        "Quick Role Autofill: Added for test/demo accounts so review meetings run effortlessly."
    ]
    for b in pts:
        p = tf.add_paragraph()
        p.text = "• " + b if not b.startswith("  -") else b
        p.font.size = Pt(11)
        p.font.color.rgb = C_MUTED
        p.space_after = Pt(4)

    # Right: Login Screenshot
    if os.path.exists(img_login):
        add_card(s5, Inches(5.6), Inches(1.45), Inches(6.93), Inches(5.6))
        try:
            s5.shapes.add_picture(img_login, Inches(5.75), Inches(1.6), width=Inches(6.63))
        except:
            pass

    # ==========================================
    # SLIDE 6: Screenshot: Executive Dashboard Overview
    # ==========================================
    s6 = prs.slides.add_slide(blank_layout)
    set_bg(s6)
    add_header(s6, "Screenshot: Live Executive Dashboard & Navigation")

    # Full dashboard screenshot taking main focus
    if os.path.exists(img_dash):
        add_card(s6, Inches(0.8), Inches(1.45), Inches(11.73), Inches(4.3))
        try:
            s6.shapes.add_picture(img_dash, Inches(0.95), Inches(1.55), width=Inches(11.43))
        except:
            pass

    # Bottom 3 summary cards
    b_w = Inches(3.7)
    b_h = Inches(1.3)
    y_sub = Inches(5.95)

    sub_items = [
        ("TOP NAVIGATION TABS", "Fast access to Schemes, Fund Claims, Monthly Progress, Site Monitoring, and MIS Cockpit.", C_BLUE),
        ("LIVE FINANCIAL METRICS", "Instant view of total ₹3,740 Cr sanctioned vs ₹205 Cr released across all 30 river projects.", C_GREEN),
        ("INTERACTIVE RIVER MAP", "Satellite & terrain GIS map showing exact on-ground location of all embankments and spurs.", C_NAVY)
    ]
    for i, (h_t, sub_t, col) in enumerate(sub_items):
        bx = Inches(0.8) + i * (b_w + Inches(0.31))
        add_card(s6, bx, y_sub, b_w, b_h)
        tx = s6.shapes.add_textbox(bx + Inches(0.15), y_sub + Inches(0.12), b_w - Inches(0.3), b_h - Inches(0.24))
        tf = tx.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = h_t
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = col
        p = tf.add_paragraph()
        p.text = sub_t
        p.font.size = Pt(10)
        p.font.color.rgb = C_MUTED

    # ==========================================
    # SLIDE 7: Screenshot: Interactive GIS Basin Map
    # ==========================================
    s7 = prs.slides.add_slide(blank_layout)
    set_bg(s7)
    add_header(s7, "Screenshot: Interactive GIS River Basin Map")

    # Left: GIS Map screenshot
    if os.path.exists(img_map):
        add_card(s7, Inches(0.8), Inches(1.45), Inches(7.2), Inches(5.6))
        try:
            s7.shapes.add_picture(img_map, Inches(0.95), Inches(1.6), width=Inches(6.9))
        except:
            pass

    # Right: How to Read & Use It
    w_rt = Inches(4.2)
    add_card(s7, Inches(8.33), Inches(1.45), w_rt, Inches(5.6))
    tx = s7.shapes.add_textbox(Inches(8.55), Inches(1.75), w_rt - Inches(0.4), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "HOW OFFICIALS USE THE MAP"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    p = tf.add_paragraph()
    p.text = "Visual Ground Intelligence"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    map_points = [
        "Color-Coded Status Pins:",
        "  🟢 Green = 100% Completed Works",
        "  🟡 Yellow = Ongoing Construction",
        "  🔵 Blue = Initial Planning / Tender",
        "Filter by River Basin: Click Teesta, Barak, Dhansiri, Lohit, or Siang to see only that river.",
        "One-Click Fly To Site: Clicking any project in the right list immediately zooms the camera to the riverbank.",
        "Works on Any Device: Runs in standard web browser on laptop, tablet, or phone."
    ]
    for m in map_points:
        p = tf.add_paragraph()
        p.text = m if m.startswith("  ") else "• " + m
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_MUTED
        p.space_after = Pt(3)

    # ==========================================
    # SLIDE 8: GFR 12-A Made Simple
    # ==========================================
    s8 = prs.slides.add_slide(blank_layout)
    set_bg(s8)
    add_header(s8, "Fund Releases & GFR 12-A: Made Simple & Automated")

    col1_w = Inches(5.65)
    col2_w = Inches(5.65)
    add_card(s8, Inches(0.8), Inches(1.45), col1_w, Inches(5.6))
    tx = s8.shapes.add_textbox(Inches(1.1), Inches(1.75), col1_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "WHAT IS FORM GFR 12-A?"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    p = tf.add_paragraph()
    p.text = "Statutory Utilization Certificate (UC)"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    gfr_expl = [
        ("The Rule", "Under Government Rule 238(1), states cannot receive next fund installments without submitting certified proof that prior money was spent on the river."),
        ("The Problem in Past", "State officers had to manually draft GFR 12-A tables by hand, resulting in frequent audit objections, math mismatches, and delayed grants."),
        ("The Solution in Portal", "The portal does the math automatically. It remembers past sanctions, spent money, interest, and auto-generates the official Form GFR 12-A PDF.")
    ]
    for h_txt, b_txt in gfr_expl:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_MUTED

    # Right: How it works in Portal
    add_card(s8, Inches(6.85), Inches(1.45), col2_w, Inches(5.6))
    tx = s8.shapes.add_textbox(Inches(7.15), Inches(1.75), col2_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "IN THE PORTAL: 3 SIMPLE CLICKS"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_GREEN

    p = tf.add_paragraph()
    p.text = "Zero Calculation Errors"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    steps_gfr = [
        ("1. Click Auto-Generate", "State officer selects the scheme and installment. The portal pre-fills all historical sanction balances."),
        ("2. Verify & Certify", "Officer enters contractor voucher figures. Portal instantly verifies totals match."),
        ("3. Download Official PDF", "Generates official Form GFR 12-A PDF with digital verification barcode and attaches it directly to the claim.")
    ]
    for h_txt, b_txt in steps_gfr:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_MUTED

    # ==========================================
    # SLIDE 9: Field Inspection with Photos
    # ==========================================
    s9 = prs.slides.add_slide(blank_layout)
    set_bg(s9)
    add_header(s9, "Field Inspection: Proof of Real Work on the Riverbank")

    add_card(s9, Inches(0.8), Inches(1.45), col1_w, Inches(5.6))
    tx = s9.shapes.add_textbox(Inches(1.1), Inches(1.75), col1_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "NO FUNDS WITHOUT PROOF"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    p = tf.add_paragraph()
    p.text = "GPS Geotagged Site Verification"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    audit_pts = [
        ("Mandatory GPS Coordinates", "Photos taken on site must contain real GPS latitude, longitude, and date. Fake or old photos cannot be uploaded."),
        ("River Quality Checklists", "Inspectors grade boulder pitching, geo-bag stability, embankment height, and revetment condition."),
        ("Physical % vs Claimed Money", "System compares physical progress with money requested. If physical work is 40% but claim is 80%, system blocks the release."),
        ("Senior Officers Can Verify", "Chairman & Chief Engineers can click on any photo in Guwahati to see exactly where it was taken.")
    ]
    for h_txt, b_txt in audit_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_MUTED

    # Right: Pre-Monsoon readiness
    add_card(s9, Inches(6.85), Inches(1.45), col2_w, Inches(5.6))
    tx = s9.shapes.add_textbox(Inches(7.15), Inches(1.75), col2_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "TIMELY FLOOD SEASON READINESS"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_AMBER

    p = tf.add_paragraph()
    p.text = "Saving Lives & Embankments"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    season_pts = [
        ("Pre-Monsoon Inspection (March-April)", "Ensures anti-erosion spurs and breach closures are finished before high-flood waters arrive."),
        ("During Monsoon Flood Tracking", "Rapid reporting of embankment overflows or damage requiring emergency central intervention."),
        ("Post-Monsoon Impact Report", "Validates how effectively the embankment protected nearby villages and agricultural lands."),
        ("Audit Ready for Parliament/CAG", "All reports, photos, and remarks compiled into an exportable PDF dossier.")
    ]
    for h_txt, b_txt in season_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_MUTED

    # ==========================================
    # SLIDE 10: Pending Files Tracker (No Lost Files)
    # ==========================================
    s10 = prs.slides.add_slide(blank_layout)
    set_bg(s10)
    add_header(s10, "Pending Files Tracker: Knowing Exactly Whose Desk It's On")

    add_card(s10, Inches(0.8), Inches(1.45), col1_w, Inches(5.6))
    tx = s10.shapes.add_textbox(Inches(1.1), Inches(1.75), col1_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "TOTAL ACCOUNTABILITY"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    p = tf.add_paragraph()
    p.text = "Where is the File Right Now?"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    radar_pts = [
        ("Clear Desk Identification", "Every project and fund claim shows exactly who has to take action: State WRD, Brahmaputra Board, or MoJS."),
        ("Days Elapsed Counter", "Shows how many days a proposal has been waiting (e.g. 'Pending for 12 days')."),
        ("Red Alert for Old Files", "Files delayed over 15 or 30 days are automatically highlighted in red so leaders can follow up."),
        ("Instant Excel Export", "Entire list of pending and completed files can be exported to Excel in one click for review meetings.")
    ]
    for h_txt, b_txt in radar_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_MUTED

    add_card(s10, Inches(6.85), Inches(1.45), col2_w, Inches(5.6))
    tx = s10.shapes.add_textbox(Inches(7.15), Inches(1.75), col2_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "BENEFITS FOR BOARD LEADERSHIP"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_GREEN

    p = tf.add_paragraph()
    p.text = "Informed Decision Making"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    lead_pts = [
        ("No More 'Lost in Transit'", "No department can say they did not receive the proposal; date and time are digitally stamped."),
        ("Identify Bottlenecks", "Shows which states or divisions submit claims on time vs who has recurring pendency."),
        ("Ready for Parliamentary Questions", "Gives exact figures of funds released and work done in seconds, without calling multiple field offices."),
        ("One-Click Meeting Briefs", "Generates summary PDF reports for meetings with Ministry Joint Secretary or State Ministers.")
    ]
    for h_txt, b_txt in lead_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_MUTED

    # ==========================================
    # SLIDE 11: Portal is Live on Cloud Today
    # ==========================================
    s11 = prs.slides.add_slide(blank_layout)
    set_bg(s11)
    add_header(s11, "Current Deployment Status: Live on Public Cloud")

    add_card(s11, Inches(0.8), Inches(1.45), col1_w, Inches(5.6))
    tx = s11.shapes.add_textbox(Inches(1.1), Inches(1.75), col1_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "PUBLIC ACCESSIBILITY"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    p = tf.add_paragraph()
    p.text = "Live Web Address"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    live_pts = [
        ("Public Internet Address", "https://103.162.247.169"),
        ("No VPN Required for Users", "Accessible directly in Chrome, Edge, or Safari from any office, home, or smartphone."),
        ("Official Login Credentials Active", "Accounts configured for Super Admin, Board Officials, State Officials (Assam, Meghalaya), and Ministry (MoJS)."),
        ("Preloaded Live Data", "All 30 actual schemes, ₹3,740 Cr outlay, and river basin coordinates already loaded.")
    ]
    for h_txt, b_txt in live_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_MUTED

    add_card(s11, Inches(6.85), Inches(1.45), col2_w, Inches(5.6))
    tx = s11.shapes.add_textbox(Inches(7.15), Inches(1.75), col2_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "SECURITY & RELIABILITY"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_GREEN

    p = tf.add_paragraph()
    p.text = "Enterprise Cloud Setup"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    infra_pts = [
        ("Cloud Server Capacity", "8 vCPU, 16 GB RAM, dedicated 70 GB document storage for flood reports and photos."),
        ("24/7 Auto-Recovery Watchdog", "Configured to automatically restart in 3 seconds if any server reboot or disruption occurs."),
        ("Daily Automated Backups", "Database and uploaded certificates backed up to ensure zero data loss."),
        ("Next Step with NIC", "Mapping the official website name: fmbap.brahmaputraboard.gov.in with official Government SSL.")
    ]
    for h_txt, b_txt in infra_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_MUTED

    # ==========================================
    # SLIDE 12: Summary of Benefits
    # ==========================================
    s12 = prs.slides.add_slide(blank_layout)
    set_bg(s12)
    add_header(s12, "Summary: Big Wins for the Brahmaputra Board")

    b_card_w = Inches(11.73)
    add_card(s12, Inches(0.8), Inches(1.45), b_card_w, Inches(5.6))
    tx = s12.shapes.add_textbox(Inches(1.1), Inches(1.75), b_card_w - Inches(0.6), Inches(5.0))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "KEY TAKEAWAYS FOR BOARD REVIEW"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    final_wins = [
        ("1. 100% Digital Transparency", "From DPR submission to final fund disbursal, everything is logged digitally. Eliminates paper file loss and communication gaps with state departments."),
        ("2. Genuine Ground Proof", "Every payment recommendation is backed by real GPS photos of embankments and spurs. Protects Board officials from audit objections."),
        ("3. Instant GFR 12-A Compliance", "State departments can generate statutory Utilization Certificates in 3 clicks with zero math mistakes, keeping central funds flowing smoothly."),
        ("4. Executive Control at a Glance", "Interactive river basin GIS map and pendency alarms give the Chairman and Board Members instant command over all 30 flood management schemes."),
        ("5. Live & Ready Today", "The portal is fully built, loaded with real baseline data, and running live on the cloud at https://103.162.247.169.")
    ]
    for h_txt, b_txt in final_wins:
        p = tf.add_paragraph()
        p.text = "\n" + h_txt
        p.font.size = Pt(13)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "   " + b_txt
        p.font.size = Pt(11.5)
        p.font.color.rgb = C_MUTED

    prs.save(output_file)
    print(f"Simple presentation created at: {output_file}")

if __name__ == "__main__":
    out = r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Board_Review_Simple.pptx"
    build_presentation(out)
