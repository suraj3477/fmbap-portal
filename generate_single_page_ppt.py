import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE

def build_single_page_presentation(output_file):
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # Theme Colors - Official Government Palette
    C_NAVY = RGBColor(11, 37, 69)          # Deep Official Navy #0B2545
    C_BLUE = RGBColor(15, 76, 159)         # Govt Primary Blue #0F4C9F
    C_BG = RGBColor(246, 248, 251)         # Clean soft background #F6F8FB
    C_WHITE = RGBColor(255, 255, 255)
    C_BORDER = RGBColor(226, 232, 240)     # Subtle card border #E2E8F0
    C_DARK = RGBColor(15, 23, 42)          # Text charcoal #0F172A
    C_MUTED = RGBColor(71, 85, 105)        # Text slate #475569
    C_GREEN = RGBColor(22, 163, 74)        # Success Green #16A34A
    C_AMBER = RGBColor(217, 119, 6)        # Amber #D97706
    C_GOLD = RGBColor(245, 158, 11)        # Accent Gold #F59E0B
    C_LIGHT_BLUE = RGBColor(239, 246, 255) # Soft Blue tint #EFF6FF
    C_LIGHT_GREEN = RGBColor(240, 253, 244)# Soft Green tint #F0FDF4

    # Paths
    img_logo = r"d:\fmbap-portal-main - Copy\public\logo.png"
    img_dash = r"d:\fmbap-portal-main - Copy\screenshots\live_02_dashboard.png"

    slide = prs.slides.add_slide(blank_layout)

    # 1. Slide Background
    bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
    bg.fill.solid()
    bg.fill.fore_color.rgb = C_BG
    bg.line.fill.background()

    # 2. Header Banner (Height = 1.05")
    hb = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, Inches(1.05))
    hb.fill.solid()
    hb.fill.fore_color.rgb = C_NAVY
    hb.line.fill.background()

    # Gold Accent Stripe
    gl = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, Inches(1.05), prs.slide_width, Inches(0.04))
    gl.fill.solid()
    gl.fill.fore_color.rgb = C_GOLD
    gl.line.fill.background()

    # Header Text
    tx_h = slide.shapes.add_textbox(Inches(0.8), Inches(0.12), Inches(10.5), Inches(0.85))
    tf_h = tx_h.text_frame
    tf_h.word_wrap = True
    tf_h.margin_left = tf_h.margin_top = tf_h.margin_right = tf_h.margin_bottom = 0

    p_sub = tf_h.paragraphs[0]
    p_sub.text = "GOVERNMENT OF INDIA • MINISTRY OF JAL SHAKTI • BRAHMAPUTRA BOARD"
    p_sub.font.size = Pt(9.5)
    p_sub.font.bold = True
    p_sub.font.color.rgb = C_GOLD

    p_main = tf_h.add_paragraph()
    p_main.text = "FMBAP Digital Monitoring Portal — Executive One-Pager"
    p_main.font.size = Pt(21)
    p_main.font.bold = True
    p_main.font.color.rgb = C_WHITE

    # Logo / Emblem on top right
    if os.path.exists(img_logo):
        try:
            slide.shapes.add_picture(img_logo, Inches(12.1), Inches(0.16), height=Inches(0.72))
        except:
            pass

    # Helper function for rounded cards
    def add_card(x, y, w, h, bg_col=C_WHITE, border_col=C_BORDER, border_w=1.0):
        c = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x, y, w, h)
        c.fill.solid()
        c.fill.fore_color.rgb = bg_col
        if border_col:
            c.line.color.rgb = border_col
            c.line.width = Pt(border_w)
        else:
            c.line.fill.background()
        return c

    # =========================================================================
    # 3. KPI Metric Strip (4 Cards across top)
    # =========================================================================
    y_kpi = Inches(1.18)
    h_kpi = Inches(0.95)
    w_kpi = Inches(2.78)
    gap_kpi = Inches(0.20)
    x_kpi_start = Inches(0.8)

    kpi_data = [
        ("TOTAL SANCTIONED OUTLAY", "₹3,740.81 Cr", "30 Schemes • 90:10 Central:State Ratio", C_BLUE, C_LIGHT_BLUE),
        ("CENTRAL ASSISTANCE RELEASED", "₹205.47 Cr", "Verified Against GFR 12-A UCs", C_GREEN, C_LIGHT_GREEN),
        ("EXECUTION STATUS", "17 / 30 Schemes", "17 Completed • 5 Active • 8 Tendering", C_NAVY, C_WHITE),
        ("AVG PHYSICAL PROGRESS", "66.2%", "Weighted On-Ground Engineering Audits", C_AMBER, C_WHITE)
    ]

    for i, (k_label, k_val, k_sub, k_col, k_bg) in enumerate(kpi_data):
        kx = x_kpi_start + i * (w_kpi + gap_kpi)
        add_card(kx, y_kpi, w_kpi, h_kpi, bg_col=C_WHITE, border_col=C_BORDER)

        # Left color indicator strip inside card
        strip = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, kx, y_kpi, Inches(0.08), h_kpi)
        strip.fill.solid()
        strip.fill.fore_color.rgb = k_col
        strip.line.fill.background()

        ktx = slide.shapes.add_textbox(kx + Inches(0.18), y_kpi + Inches(0.08), w_kpi - Inches(0.24), h_kpi - Inches(0.16))
        ktf = ktx.text_frame
        ktf.word_wrap = True
        ktf.margin_left = ktf.margin_top = ktf.margin_right = ktf.margin_bottom = 0

        p1 = ktf.paragraphs[0]
        p1.text = k_label
        p1.font.size = Pt(8.5)
        p1.font.bold = True
        p1.font.color.rgb = C_MUTED

        p2 = ktf.add_paragraph()
        p2.text = k_val
        p2.font.size = Pt(17)
        p2.font.bold = True
        p2.font.color.rgb = k_col

        p3 = ktf.add_paragraph()
        p3.text = k_sub
        p3.font.size = Pt(8.5)
        p3.font.color.rgb = C_DARK

    # =========================================================================
    # 4. Main Body: Split into Left (Live Showcase) & Right (Governance Pillars)
    # =========================================================================
    y_body = Inches(2.22)
    h_body = Inches(4.55)

    # --- LEFT CONTAINER: Live Portal Showcase (Width = 7.35") ---
    w_left = Inches(7.35)
    add_card(Inches(0.8), y_body, w_left, h_body, bg_col=C_WHITE, border_col=C_BORDER)

    # Title badge for screenshot
    ltx = slide.shapes.add_textbox(Inches(0.98), y_body + Inches(0.12), w_left - Inches(0.36), Inches(0.32))
    ltf = ltx.text_frame
    ltf.word_wrap = True
    ltf.margin_left = ltf.margin_top = ltf.margin_right = ltf.margin_bottom = 0
    lp = ltf.paragraphs[0]
    lp.text = "● LIVE SYSTEM VIEW: EXECUTIVE DASHBOARD & INTERACTIVE GIS BASIN MAP"
    lp.font.size = Pt(9.5)
    lp.font.bold = True
    lp.font.color.rgb = C_BLUE

    # Embedded Screenshot
    if os.path.exists(img_dash):
        try:
            pic_x = Inches(0.98)
            pic_y = y_body + Inches(0.46)
            pic_w = w_left - Inches(0.36)  # ~6.99"
            pic_h = Inches(3.40)
            slide.shapes.add_picture(img_dash, pic_x, pic_y, width=pic_w, height=pic_h)
        except Exception as e:
            print("Error embedding dashboard screenshot:", e)

    # Quick Feature Tags below Screenshot
    feat_y = y_body + Inches(3.95)
    feat_w = (w_left - Inches(0.48)) / 2
    feats = [
        ("Interactive GIS River Basin Map", "Satellite & terrain tracking across Teesta, Barak, Dhansiri, Lohit, Simsang & Siang basins."),
        ("One-Click Departmental Access", "Instant navigation to Schemes Catalogue, Fund Claims, Monthly Milestones & MIS Cockpit.")
    ]
    for fi, (ft_title, ft_desc) in enumerate(feats):
        fx = Inches(0.98) + fi * (feat_w + Inches(0.12))
        add_card(fx, feat_y, feat_w, Inches(0.48), bg_col=RGBColor(248, 250, 252), border_col=C_BORDER)
        ftx = slide.shapes.add_textbox(fx + Inches(0.10), feat_y + Inches(0.04), feat_w - Inches(0.20), Inches(0.40))
        ftf = ftx.text_frame
        ftf.word_wrap = True
        ftf.margin_left = ftf.margin_top = ftf.margin_right = ftf.margin_bottom = 0

        fp1 = ftf.paragraphs[0]
        fp1.text = "✓ " + ft_title
        fp1.font.size = Pt(8.5)
        fp1.font.bold = True
        fp1.font.color.rgb = C_DARK

        fp2 = ftf.add_paragraph()
        fp2.text = ft_desc
        fp2.font.size = Pt(7.5)
        fp2.font.color.rgb = C_MUTED

    # --- RIGHT CONTAINER: 4 Core Value Pillars (Width = 4.18") ---
    x_right = Inches(8.35)
    w_right = Inches(4.18)
    add_card(x_right, y_body, w_right, h_body, bg_col=C_WHITE, border_col=C_BORDER)

    # Right Container Header
    rtx = slide.shapes.add_textbox(x_right + Inches(0.18), y_body + Inches(0.12), w_right - Inches(0.36), Inches(0.32))
    rtf = rtx.text_frame
    rtf.word_wrap = True
    rtf.margin_left = rtf.margin_top = rtf.margin_right = rtf.margin_bottom = 0
    rp = rtf.paragraphs[0]
    rp.text = "● STRATEGIC GOVERNANCE VALUE FOR THE BOARD"
    rp.font.size = Pt(9.5)
    rp.font.bold = True
    rp.font.color.rgb = C_NAVY

    # 4 Concise Governance Pillars
    pillars = [
        ("1. ZERO-DELAY DIGITAL APPROVALS", C_BLUE,
         "Replaces 60-90 day postal routing. Instant digital file movement across State WRD → Brahmaputra Board Guwahati → Ministry (MoJS) New Delhi."),
        ("2. MANDATORY GPS PHOTO AUDITS", C_AMBER,
         "Every claim requires field photos stamped with camera GPS coordinates, timestamp & elevation. Fully protects Board leadership from CAG audit objections."),
        ("3. AUTOMATED STATUTORY FORM GFR 12-A", C_GREEN,
         "3-click Utilization Certificate generation. Pre-fills historical balances with zero arithmetic errors and exports verified Government of India PDFs."),
        ("4. EXECUTIVE PENDENCY RADAR", C_NAVY,
         "Real-time desk tracking identifies exactly whose table a file is on with an aging days clock and red-flag alerts for overdue claims.")
    ]

    p_y_start = y_body + Inches(0.46)
    p_h = Inches(0.96)
    p_gap = Inches(0.06)

    for pi, (p_title, p_col, p_desc) in enumerate(pillars):
        py = p_y_start + pi * (p_h + p_gap)
        add_card(x_right + Inches(0.14), py, w_right - Inches(0.28), p_h, bg_col=RGBColor(249, 250, 252), border_col=C_BORDER)

        # Left color bar
        bar = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, x_right + Inches(0.14), py, Inches(0.06), p_h)
        bar.fill.solid()
        bar.fill.fore_color.rgb = p_col
        bar.line.fill.background()

        ptx = slide.shapes.add_textbox(x_right + Inches(0.26), py + Inches(0.08), w_right - Inches(0.44), p_h - Inches(0.14))
        ptf = ptx.text_frame
        ptf.word_wrap = True
        ptf.margin_left = ptf.margin_top = ptf.margin_right = ptf.margin_bottom = 0

        pp1 = ptf.paragraphs[0]
        pp1.text = p_title
        pp1.font.size = Pt(9.5)
        pp1.font.bold = True
        pp1.font.color.rgb = p_col

        pp2 = ptf.add_paragraph()
        pp2.text = p_desc
        pp2.font.size = Pt(8.5)
        pp2.font.color.rgb = C_DARK
        pp2.space_before = Pt(2)

    # =========================================================================
    # 5. Footer Strip (Deployment & Senior Authority Sign-Off)
    # =========================================================================
    y_foot = Inches(6.85)
    h_foot = Inches(0.52)
    w_foot = Inches(11.73)

    foot = add_card(Inches(0.8), y_foot, w_foot, h_foot, bg_col=C_NAVY, border_col=None)

    ftx = slide.shapes.add_textbox(Inches(0.95), y_foot + Inches(0.10), w_foot - Inches(0.3), Inches(0.32))
    ftf = ftx.text_frame
    ftf.word_wrap = True
    ftf.margin_left = ftf.margin_top = ftf.margin_right = ftf.margin_bottom = 0

    p_f = ftf.paragraphs[0]
    p_f.text = "LIVE CLOUD PORTAL:  https://103.162.247.169/dashboard   •   24/7 AUTO-RECOVERY WATCHDOG   •   PRELOADED WITH ALL 30 APPROVED SCHEMES"
    p_f.font.size = Pt(9.5)
    p_f.font.bold = True
    p_f.font.color.rgb = C_GOLD
    p_f.alignment = PP_ALIGN.CENTER

    prs.save(output_file)
    print(f"Executive single-page presentation created at: {output_file}")

if __name__ == "__main__":
    out = r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Executive_OnePager.pptx"
    build_single_page_presentation(out)
