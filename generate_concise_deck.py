import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE

def build_concise_presentation(output_file):
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # Executive Color Palette
    C_NAVY = RGBColor(11, 37, 69)         # Deep Official Navy #0B2545
    C_BLUE = RGBColor(15, 76, 159)        # Govt Blue #0F4C9F
    C_SLATE_BG = RGBColor(248, 250, 252)  # Ultra-clean soft slate #F8FAFC
    C_WHITE = RGBColor(255, 255, 255)
    C_BORDER = RGBColor(226, 232, 240)    # Soft card border #E2E8F0
    C_DARK = RGBColor(15, 23, 42)         # Crisp charcoal #0F172A
    C_MUTED = RGBColor(71, 85, 105)       # Muted slate #475569
    C_GREEN = RGBColor(22, 163, 74)       # Success Green #16A34A
    C_AMBER = RGBColor(217, 119, 6)       # Amber Gold #D97706
    C_GOLD = RGBColor(245, 158, 11)       # Accent Gold #F59E0B
    C_RED = RGBColor(220, 38, 38)         # Danger Red #DC2626

    # Assets
    img_logo = r"d:\fmbap-portal-main - Copy\public\logo.png"
    s_dir = r"d:\fmbap-portal-main - Copy\screenshots"
    img_login = os.path.join(s_dir, "live_01_login.png")
    img_dash = os.path.join(s_dir, "live_02_dashboard.png")
    img_schemes = os.path.join(s_dir, "live_03_schemes_catalogue.png")
    img_fund_rel = os.path.join(s_dir, "live_04_fund_release.png")
    img_gfr12a = os.path.join(s_dir, "live_05_gfr12a_create.png")
    img_progress = os.path.join(s_dir, "live_06_progress_reports.png")
    img_monitor = os.path.join(s_dir, "live_07_site_monitoring.png")
    img_analytics = os.path.join(s_dir, "live_08_analytics_cockpit.png")
    img_map = os.path.join(s_dir, "05_gis_basin_map.jpg")
    img_kpi = os.path.join(s_dir, "04_kpi_metrics.jpg")

    def set_bg(slide, color=C_SLATE_BG):
        bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
        bg.fill.solid()
        bg.fill.fore_color.rgb = color
        bg.line.fill.background()
        return bg

    def add_header(slide, title, category="BRAHMAPUTRA BOARD • FMBAP MONITORING PORTAL"):
        # Header bar
        hb = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, Inches(1.05))
        hb.fill.solid()
        hb.fill.fore_color.rgb = C_NAVY
        hb.line.fill.background()

        # Gold accent line
        gl = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, Inches(1.05), prs.slide_width, Inches(0.05))
        gl.fill.solid()
        gl.fill.fore_color.rgb = C_GOLD
        gl.line.fill.background()

        # Text
        tx = slide.shapes.add_textbox(Inches(0.8), Inches(0.14), Inches(10.5), Inches(0.82))
        tf = tx.text_frame
        tf.word_wrap = True
        tf.margin_left = tf.margin_top = tf.margin_right = tf.margin_bottom = 0

        p_cat = tf.paragraphs[0]
        p_cat.text = category.upper()
        p_cat.font.size = Pt(9.5)
        p_cat.font.bold = True
        p_cat.font.color.rgb = C_GOLD

        p_title = tf.add_paragraph()
        p_title.text = title
        p_title.font.size = Pt(20)
        p_title.font.bold = True
        p_title.font.color.rgb = C_WHITE

        # Logo on right
        if os.path.exists(img_logo):
            try:
                slide.shapes.add_picture(img_logo, Inches(12.1), Inches(0.18), height=Inches(0.72))
            except:
                pass

    def add_card(slide, x, y, w, h, bg=C_WHITE, border=C_BORDER):
        card = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x, y, w, h)
        card.fill.solid()
        card.fill.fore_color.rgb = bg
        if border:
            card.line.color.rgb = border
            card.line.width = Pt(1.0)
        else:
            card.line.fill.background()
        return card

    def add_screenshot_card(slide, x, y, w, h, img_path, label="LIVE PORTAL SCREEN"):
        # Outer Card
        add_card(slide, x, y, w, h, bg=C_WHITE, border=C_BORDER)

        # Label badge on top of card
        lbl_box = slide.shapes.add_textbox(x + Inches(0.2), y + Inches(0.1), w - Inches(0.4), Inches(0.3))
        ltf = lbl_box.text_frame
        ltf.word_wrap = True
        ltf.margin_left = ltf.margin_top = ltf.margin_right = ltf.margin_bottom = 0
        lp = ltf.paragraphs[0]
        lp.text = "● " + label.upper()
        lp.font.size = Pt(8.5)
        lp.font.bold = True
        lp.font.color.rgb = C_BLUE

        # Picture inside card
        if os.path.exists(img_path):
            try:
                pic_x = x + Inches(0.15)
                pic_y = y + Inches(0.42)
                pic_w = w - Inches(0.3)
                pic_h = h - Inches(0.55)
                slide.shapes.add_picture(img_path, pic_x, pic_y, width=pic_w, height=pic_h)
            except Exception as e:
                print(f"Error adding {img_path}: {e}")

    # =========================================================================
    # SLIDE 1: Title Slide (Concise & Impactful)
    # =========================================================================
    s1 = prs.slides.add_slide(blank_layout)
    set_bg(s1, C_NAVY)

    # Top gold accent
    t_bar = s1.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, Inches(0.12))
    t_bar.fill.solid()
    t_bar.fill.fore_color.rgb = C_GOLD
    t_bar.line.fill.background()

    # Emblem/Logo
    if os.path.exists(img_logo):
        try:
            s1.shapes.add_picture(img_logo, Inches(1.0), Inches(1.2), height=Inches(1.2))
        except:
            pass

    tx = s1.shapes.add_textbox(Inches(1.0), Inches(2.6), Inches(11.3), Inches(3.6))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "GOVERNMENT OF INDIA • MINISTRY OF JAL SHAKTI • BRAHMAPUTRA BOARD"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = C_GOLD

    p = tf.add_paragraph()
    p.text = "FMBAP Digital Monitoring Portal"
    p.font.size = Pt(36)
    p.font.bold = True
    p.font.color.rgb = C_WHITE
    p.space_before = Pt(8)

    p = tf.add_paragraph()
    p.text = "Flood Management & Border Areas Programme — Review of Online Governance & Live Portal"
    p.font.size = Pt(17)
    p.font.color.rgb = RGBColor(203, 213, 225) # Slate 300
    p.space_before = Pt(6)

    # Bottom Pill Cards
    pills = [
        ("PORTFOLIO", "30 Schemes • ₹3,740.81 Cr"),
        ("STATUTORY RULE", "Automated Form GFR 12-A"),
        ("QUALITY AUDIT", "GPS Geotagged Site Photos"),
        ("PUBLIC ACCESS", "Live at 103.162.247.169")
    ]
    pill_w = Inches(2.7)
    pill_h = Inches(0.9)
    y_pill = Inches(5.9)
    for i, (k, v) in enumerate(pills):
        px = Inches(1.0) + i * (pill_w + Inches(0.18))
        card = s1.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, px, y_pill, pill_w, pill_h)
        card.fill.solid()
        card.fill.fore_color.rgb = RGBColor(23, 49, 87)
        card.line.color.rgb = C_GOLD
        card.line.width = Pt(1)

        ptx = s1.shapes.add_textbox(px + Inches(0.15), y_pill + Inches(0.12), pill_w - Inches(0.3), pill_h - Inches(0.24))
        ptf = ptx.text_frame
        ptf.word_wrap = True
        pp1 = ptf.paragraphs[0]
        pp1.text = k
        pp1.font.size = Pt(9)
        pp1.font.bold = True
        pp1.font.color.rgb = C_GOLD

        pp2 = ptf.add_paragraph()
        pp2.text = v
        pp2.font.size = Pt(10.5)
        pp2.font.bold = True
        pp2.font.color.rgb = C_WHITE

    # =========================================================================
    # SLIDE 2: Why This Portal? (Problem vs Solution)
    # =========================================================================
    s2 = prs.slides.add_slide(blank_layout)
    set_bg(s2)
    add_header(s2, "Executive Overview: Why This Portal Was Built", "STRATEGIC GOVERNANCE MANDATE")

    w_half = Inches(5.7)
    h_card = Inches(5.7)
    y_top = Inches(1.35)

    # Left: The Old Way (Red accent)
    add_card(s2, Inches(0.8), y_top, w_half, h_card, bg=C_WHITE, border=RGBColor(254, 202, 202))
    tx = s2.shapes.add_textbox(Inches(1.1), y_top + Inches(0.25), w_half - Inches(0.6), h_card - Inches(0.5))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "THE OLD WAY  •  PAPER DELAYS & BLIND SPOTS"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = C_RED

    old_items = [
        ("60 to 90 Day Paper Routing", "Proposals and bills traveled physically via post between State WRD capitals, Guwahati, and New Delhi."),
        ("No Tamper-Proof Photo Proof", "Senior leadership had no rapid way to verify boulder pitching or spur completion before sanctioning funds."),
        ("GFR 12-A Calculation Errors", "Hand-drafted Utilization Certificates caused frequent audit discrepancies and stalled central fund releases."),
        ("Files Lost in Transit", "Zero visibility into which officer's desk an urgent flood protection proposal was held up on.")
    ]
    for h_txt, b_txt in old_items:
        p = tf.add_paragraph()
        p.text = "\n❌  " + h_txt
        p.font.size = Pt(12)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "     " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_MUTED

    # Right: The New Way (Green accent)
    add_card(s2, Inches(6.83), y_top, w_half, h_card, bg=C_WHITE, border=RGBColor(187, 247, 208))
    tx = s2.shapes.add_textbox(Inches(7.13), y_top + Inches(0.25), w_half - Inches(0.6), h_card - Inches(0.5))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "THE NEW WAY  •  REAL-TIME DIGITAL GOVERNANCE"
    p.font.size = Pt(12)
    p.font.bold = True
    p.font.color.rgb = C_GREEN

    new_items = [
        ("Online Approvals in 24-48 Hours", "State proposals, bills, and engineering reports submitted digitally with immutable audit stamps."),
        ("Mandatory GPS Geotagged Photos", "Field engineers capture on-site photos embedded with exact GPS coordinates, timestamp, and elevation."),
        ("3-Click Statutory GFR 12-A", "System auto-populates opening balances and generates official certified Government UCs with zero errors."),
        ("Single National River Basin Map", "Brahmaputra Board Chairman and Chief Engineers see all 30 schemes on one live interactive map.")
    ]
    for h_txt, b_txt in new_items:
        p = tf.add_paragraph()
        p.text = "\n✅  " + h_txt
        p.font.size = Pt(12)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p = tf.add_paragraph()
        p.text = "     " + b_txt
        p.font.size = Pt(10.5)
        p.font.color.rgb = C_MUTED

    # =========================================================================
    # SLIDE 3: Live Executive Dashboard & Master Financials
    # =========================================================================
    s3 = prs.slides.add_slide(blank_layout)
    set_bg(s3)
    add_header(s3, "Executive Dashboard: Single-Pane Command Center", "LIVE PORTAL DEMONSTRATION")

    # Left: Hero Screenshot of Dashboard
    w_scr = Inches(8.0)
    add_screenshot_card(s3, Inches(0.8), Inches(1.35), w_scr, Inches(5.7), img_dash, "LIVE EXECUTIVE DASHBOARD (SCREENSHOT)")

    # Right: 4 Core Live Metrics
    w_info = Inches(3.73)
    add_card(s3, Inches(8.93), Inches(1.35), w_info, Inches(5.7))
    tx = s3.shapes.add_textbox(Inches(9.13), Inches(1.55), w_info - Inches(0.4), Inches(5.3))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "LIVE SYSTEM STATUS"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    p = tf.add_paragraph()
    p.text = "Master Portfolio KPIs"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    dash_metrics = [
        ("₹3,740.81 Cr", "TOTAL SANCTIONED OUTLAY", "Approved across 30 schemes (90% Central : 10% State special category ratio).", C_BLUE),
        ("₹205.47 Cr", "CENTRAL FUNDS RELEASED", "Disbursed strictly against verified GFR 12-A Utilization Certificates.", C_GREEN),
        ("17 / 30 Schemes", "EXECUTION STATUS", "17 completed, 5 under active construction, 8 in initial tendering phase.", C_DARK),
        ("66.2%", "AVG PHYSICAL PROGRESS", "Weighted field completion verified by Brahmaputra Board site inspections.", C_AMBER)
    ]
    for val, title, desc, col in dash_metrics:
        p = tf.add_paragraph()
        p.text = "\n" + val
        p.font.size = Pt(15)
        p.font.bold = True
        p.font.color.rgb = col

        p = tf.add_paragraph()
        p.text = title
        p.font.size = Pt(9.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK

        p = tf.add_paragraph()
        p.text = desc
        p.font.size = Pt(9)
        p.font.color.rgb = C_MUTED

    # =========================================================================
    # SLIDE 4: Interactive River Basin GIS Map
    # =========================================================================
    s4 = prs.slides.add_slide(blank_layout)
    set_bg(s4)
    add_header(s4, "Interactive GIS River Basin Map: Real-Time Spatial Tracking", "SPATIAL RIVER MONITORING")

    # Left: GIS Map Screenshot
    add_screenshot_card(s4, Inches(0.8), Inches(1.35), w_scr, Inches(5.7), img_map, "RIVER BASIN GIS MAP (SCREENSHOT)")

    # Right: Spatial Capabilities
    add_card(s4, Inches(8.93), Inches(1.35), w_info, Inches(5.7))
    tx = s4.shapes.add_textbox(Inches(9.13), Inches(1.55), w_info - Inches(0.4), Inches(5.3))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "GEOGRAPHIC INTELLIGENCE"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    p = tf.add_paragraph()
    p.text = "Locating Every River Work"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    map_points = [
        ("Sub-Basin One-Click Filters", "Filter projects instantly across Teesta, Barak (Howrah, Tlawng, Jiri), Dhansiri, Lohit, Simsang, and Siang."),
        ("Multi-Layer Satellite View", "Toggle between standard river basin vector topography and high-resolution satellite imagery."),
        ("Status Color-Coded Pins", "Visual pins: Green = Completed (17), Amber = In Progress (5), Blue = Initial Tendering (8)."),
        ("1-Click Fly-To Scheme", "Clicking any scheme in the side panel automatically flies the camera directly to that riverbank coordinate.")
    ]
    for h_txt, b_txt in map_points:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = C_DARK

        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(9.5)
        p.font.color.rgb = C_MUTED

    # =========================================================================
    # SLIDE 5: Schemes Catalogue & Baseline Master
    # =========================================================================
    s5 = prs.slides.add_slide(blank_layout)
    set_bg(s5)
    add_header(s5, "Schemes Catalogue: Codified National Register of All 30 Schemes", "SCHEME BASELINE MANAGEMENT")

    # Left: Schemes Catalogue Screenshot
    add_screenshot_card(s5, Inches(0.8), Inches(1.35), w_scr, Inches(5.7), img_schemes, "SCHEMES CATALOGUE & REGISTER (SCREENSHOT)")

    # Right: Key Governance Points
    add_card(s5, Inches(8.93), Inches(1.35), w_info, Inches(5.7))
    tx = s5.shapes.add_textbox(Inches(9.13), Inches(1.55), w_info - Inches(0.4), Inches(5.3))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "CENTRAL SCHEME REGISTER"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    p = tf.add_paragraph()
    p.text = "Replacing Disconnected Sheets"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    scheme_pts = [
        ("Immutable Scheme Codes", "Unique IDs (e.g. SK-01, AS-12) permanently link all DPRs, fund claims, and site photos."),
        ("90:10 Funding Auto-Split", "System locks approved Central Assistance (90%) and State matching share (10%) upon DPR sanction."),
        ("Live Filter by State & Status", "Filter instantly across Assam, Meghalaya, Sikkim, Manipur, Tripura, and Arunachal Pradesh."),
        ("Exportable Baseline Dossier", "Clicking any scheme opens complete hydraulic specifications, geo-coordinates, and sanction timeline.")
    ]
    for h_txt, b_txt in scheme_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = C_DARK

        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(9.5)
        p.font.color.rgb = C_MUTED

    # =========================================================================
    # SLIDE 6: Statutory Form GFR 12-A — 3-Click Automated UC
    # =========================================================================
    s6 = prs.slides.add_slide(blank_layout)
    set_bg(s6)
    add_header(s6, "Form GFR 12-A: 3-Click Statutory Utilization Certificate", "STATUTORY FINANCIAL COMPLIANCE")

    # Left: GFR 12-A Form Screenshot
    add_screenshot_card(s6, Inches(0.8), Inches(1.35), w_scr, Inches(5.7), img_gfr12a, "GFR 12-A AUTOMATED GENERATOR (SCREENSHOT)")

    # Right: GFR 12-A Capabilities
    add_card(s6, Inches(8.93), Inches(1.35), w_info, Inches(5.7))
    tx = s6.shapes.add_textbox(Inches(9.13), Inches(1.55), w_info - Inches(0.4), Inches(5.3))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "STATUTORY AUTOMATION"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_GREEN

    p = tf.add_paragraph()
    p.text = "Zero Calculation Errors"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    gfr_pts = [
        ("Rule 238(1) Compliance", "Mandatory under General Financial Rules (GFR 2017) to release subsequent Central Assistance installments."),
        ("Auto-Calculates Balances", "Pre-fills opening unspent funds, interest accrued, and previous grants. Eliminates hand-drafted arithmetic errors."),
        ("Instant Claim Verification", "System verifies contractor vouchers against sanctioned balance before permitting submission."),
        ("Official Barcoded PDF", "Generates the official Government of India Form GFR 12-A with digital verification tag ready for signing.")
    ]
    for h_txt, b_txt in gfr_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = C_DARK

        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(9.5)
        p.font.color.rgb = C_MUTED

    # =========================================================================
    # SLIDE 7: Fund Release Claims Pipeline
    # =========================================================================
    s7 = prs.slides.add_slide(blank_layout)
    set_bg(s7)
    add_header(s7, "Fund Release Claims: Multi-Tier Approval Pipeline", "DISBURSAL & AUDIT TRAIL")

    # Left: Fund Releases Screenshot
    add_screenshot_card(s7, Inches(0.8), Inches(1.35), w_scr, Inches(5.7), img_fund_rel, "FUND RELEASE CLAIMS PIPELINE (SCREENSHOT)")

    # Right: Approval Workflow
    add_card(s7, Inches(8.93), Inches(1.35), w_info, Inches(5.7))
    tx = s7.shapes.add_textbox(Inches(9.13), Inches(1.55), w_info - Inches(0.4), Inches(5.3))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "THREE-TIER CONCURRENCE"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    p = tf.add_paragraph()
    p.text = "Strict Multi-Officer Scrutiny"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    fund_pts = [
        ("Tier 1: State WRD Submission", "State department uploads bill vouchers and claims installment against sanctioned baseline."),
        ("Tier 2: Brahmaputra Board Review", "Board engineers examine physical inspection reports and recommend or return claim with remarks."),
        ("Tier 3: MoJS Sanction & PFMS", "Ministry of Jal Shakti gives final release sanction and logs PFMS transaction reference."),
        ("Immutable Audit Log", "Every approval, date, and remark is recorded permanently — protects officials during CAG audits.")
    ]
    for h_txt, b_txt in fund_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = C_DARK

        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(9.5)
        p.font.color.rgb = C_MUTED

    # =========================================================================
    # SLIDE 8: Field Monitoring & GPS Geotagged Riverbank Photos
    # =========================================================================
    s8 = prs.slides.add_slide(blank_layout)
    set_bg(s8)
    add_header(s8, "Field Monitoring: Proof of Real Work on the Riverbank", "QUALITY CONTROL & GEO-VERIFICATION")

    # Left: Site Monitoring Screenshot
    add_screenshot_card(s8, Inches(0.8), Inches(1.35), w_scr, Inches(5.7), img_monitor, "FIELD MONITORING & PHOTO LOGS (SCREENSHOT)")

    # Right: Quality & Photo Assurance
    add_card(s8, Inches(8.93), Inches(1.35), w_info, Inches(5.7))
    tx = s8.shapes.add_textbox(Inches(9.13), Inches(1.55), w_info - Inches(0.4), Inches(5.3))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "TAMPER-PROOF VERIFICATION"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_AMBER

    p = tf.add_paragraph()
    p.text = "No Funds Without Proof"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    site_pts = [
        ("Mandatory GPS Coordinates", "Photos uploaded by field engineers must contain real latitude, longitude, and timestamps taken on site."),
        ("Prevents Stock or Duplicate Photos", "System validates image metadata to ensure photos are authentic and not recycled."),
        ("Physical % vs Claimed Money", "System compares physical progress with payment claim. Blocks release if claims exceed certified work."),
        ("Pre-Monsoon Preparedness", "Audits flood spurs and breach closures in March-April before high monsoon waters arrive.")
    ]
    for h_txt, b_txt in site_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = C_DARK

        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(9.5)
        p.font.color.rgb = C_MUTED

    # =========================================================================
    # SLIDE 9: Executive MIS Cockpit & Pendency Radar
    # =========================================================================
    s9 = prs.slides.add_slide(blank_layout)
    set_bg(s9)
    add_header(s9, "Executive MIS Cockpit: Pendency Radar & Aging Clock", "DECISION SUPPORT & ACCOUNTABILITY")

    # Left: Analytics Screenshot
    add_screenshot_card(s9, Inches(0.8), Inches(1.35), w_scr, Inches(5.7), img_analytics, "EXECUTIVE MIS & PENDENCY RADAR (SCREENSHOT)")

    # Right: MIS Capabilities
    add_card(s9, Inches(8.93), Inches(1.35), w_info, Inches(5.7))
    tx = s9.shapes.add_textbox(Inches(9.13), Inches(1.55), w_info - Inches(0.4), Inches(5.3))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "EXECUTIVE RADAR"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    p = tf.add_paragraph()
    p.text = "Desk-Level Accountability"
    p.font.size = Pt(16)
    p.font.bold = True
    p.font.color.rgb = C_DARK

    mis_pts = [
        ("Where is the File Right Now?", "Identifies exactly whose desk a claim is pending on: State WRD, Board Guwahati, or MoJS Delhi."),
        ("Aging Days Elapsed Clock", "Counts how many days a proposal has been waiting (e.g. 'Pending with Board for 6 days')."),
        ("Red Alert for Overdue Files", "Automatically flags claims delayed beyond 15 or 30 days for immediate Chairman follow-up."),
        ("1-Click Excel Export", "Generates comprehensive executive summary spreadsheets for Ministry and Parliamentary reviews.")
    ]
    for h_txt, b_txt in mis_pts:
        p = tf.add_paragraph()
        p.text = "\n• " + h_txt + ":"
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = C_DARK

        p = tf.add_paragraph()
        p.text = "  " + b_txt
        p.font.size = Pt(9.5)
        p.font.color.rgb = C_MUTED

    # =========================================================================
    # SLIDE 10: Live Cloud Access & Summary of Benefits
    # =========================================================================
    s10 = prs.slides.add_slide(blank_layout)
    set_bg(s10)
    add_header(s10, "Summary: Ready for Immediate Operational Use", "DEPLOYMENT & STRATEGIC VALUE")

    # Left: Departmental Login Screenshot
    w_left_s10 = Inches(5.2)
    add_screenshot_card(s10, Inches(0.8), Inches(1.35), w_left_s10, Inches(5.7), img_login, "SECURE LOGIN PORTAL (SCREENSHOT)")

    # Right: 5 Big Wins for the Board
    w_right_s10 = Inches(6.53)
    add_card(s10, Inches(6.3), Inches(1.35), w_right_s10, Inches(5.7))
    tx = s10.shapes.add_textbox(Inches(6.55), Inches(1.55), w_right_s10 - Inches(0.5), Inches(5.3))
    tf = tx.text_frame
    tf.word_wrap = True

    p = tf.paragraphs[0]
    p.text = "LIVE CLOUD DEPLOYMENT"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_GREEN

    p = tf.add_paragraph()
    p.text = "https://103.162.247.169/dashboard"
    p.font.size = Pt(15)
    p.font.bold = True
    p.font.color.rgb = C_BLUE

    final_wins = [
        ("1. 100% Digital Transparency", "Replaces 60-day physical paper circulation across North East states with instant online approvals."),
        ("2. Tamper-Proof Photo Audits", "Mandatory GPS-tagged photos protect Brahmaputra Board officials from future CAG audit objections."),
        ("3. Automated Statutory Compliance", "Statutory Form GFR 12-A generated in 3 clicks with zero math mistakes keeps central grants moving."),
        ("4. Executive Command at a Glance", "Interactive river basin GIS map and pendency alarms provide total operational visibility to Board Leadership."),
        ("5. Enterprise 24/7 Reliability", "Live on high-performance cloud server with auto-restart watchdog; preloaded with all 30 approved schemes.")
    ]
    for h_txt, b_txt in final_wins:
        p = tf.add_paragraph()
        p.text = "\n" + h_txt
        p.font.size = Pt(11.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK

        p = tf.add_paragraph()
        p.text = "   " + b_txt
        p.font.size = Pt(9.5)
        p.font.color.rgb = C_MUTED

    prs.save(output_file)
    print(f"Concise presentation successfully created at: {output_file}")

if __name__ == "__main__":
    out_file = r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Board_Review_Simple.pptx"
    build_concise_presentation(out_file)
