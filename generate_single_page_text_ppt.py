import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE

def build_single_page_text_presentation(output_file):
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # Professional Government Palette (Strictly Text & Clean Cards)
    C_NAVY = RGBColor(11, 37, 69)          # Deep Official Navy #0B2545
    C_BLUE = RGBColor(15, 76, 159)         # Govt Primary Blue #0F4C9F
    C_BG = RGBColor(248, 250, 252)         # Ultra-clean slate #F8FAFC
    C_WHITE = RGBColor(255, 255, 255)
    C_BORDER = RGBColor(226, 232, 240)     # Clean card border #E2E8F0
    C_DARK = RGBColor(15, 23, 42)          # Primary text charcoal #0F172A
    C_MUTED = RGBColor(71, 85, 105)        # Secondary text slate #475569
    C_GREEN = RGBColor(22, 163, 74)        # Success Green #16A34A
    C_AMBER = RGBColor(217, 119, 6)        # Warm Amber #D97706
    C_GOLD = RGBColor(245, 158, 11)        # Accent Gold #F59E0B
    C_RED = RGBColor(220, 38, 38)          # Crimson Red #DC2626

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
    tx_h = slide.shapes.add_textbox(Inches(0.8), Inches(0.12), Inches(11.73), Inches(0.85))
    tf_h = tx_h.text_frame
    tf_h.word_wrap = True
    tf_h.margin_left = tf_h.margin_top = tf_h.margin_right = tf_h.margin_bottom = 0

    p_sub = tf_h.paragraphs[0]
    p_sub.text = "GOVERNMENT OF INDIA • MINISTRY OF JAL SHAKTI • BRAHMAPUTRA BOARD"
    p_sub.font.size = Pt(9.5)
    p_sub.font.bold = True
    p_sub.font.color.rgb = C_GOLD

    p_main = tf_h.add_paragraph()
    p_main.text = "Flood Management & Border Areas Programme (FMBAP) Monitoring Portal"
    p_main.font.size = Pt(20)
    p_main.font.bold = True
    p_main.font.color.rgb = C_WHITE

    p_note = tf_h.add_paragraph()
    p_note.text = "Executive Summary for Senior Leadership Review — Operational Mandate, Financial Scale & Governance Impact"
    p_note.font.size = Pt(9.5)
    p_note.font.color.rgb = RGBColor(203, 213, 225) # Slate 300

    # Helper function for cards
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
    # 3. KPI Metrics Strip (4 Cards across top)
    # =========================================================================
    y_kpi = Inches(1.18)
    h_kpi = Inches(0.96)
    w_kpi = Inches(2.78)
    gap_kpi = Inches(0.20)
    x_kpi_start = Inches(0.8)

    kpi_data = [
        ("TOTAL SANCTIONED OUTLAY", "₹3,740.81 Cr", "30 Approved Schemes • 90:10 Ratio", C_BLUE),
        ("CENTRAL FUNDS RELEASED", "₹205.47 Cr", "Verified Against GFR 12-A UCs", C_GREEN),
        ("EXECUTION STATUS", "17 / 30 Schemes", "17 Done • 5 Active • 8 Tendering", C_NAVY),
        ("AVG PHYSICAL PROGRESS", "66.2%", "Weighted On-Ground Milestone Audit", C_AMBER)
    ]

    for i, (k_label, k_val, k_sub, k_col) in enumerate(kpi_data):
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
    # 4. Main Body: 3 Structured Text Columns
    # =========================================================================
    y_body = Inches(2.24)
    h_body = Inches(4.55)
    w_col = Inches(3.74)
    gap_col = Inches(0.25)
    x_start = Inches(0.8)

    columns = [
        {
            "header_badge": "1. STRATEGIC MANDATE & NEED",
            "header_col": C_RED,
            "title": "Why This Portal Was Built",
            "points": [
                ("Ending 60-90 Day Courier Delays",
                 "In the legacy system, physical project files and fund bills took 60 to 90 days traveling between State WRD capitals, Guwahati, and New Delhi. The portal digitizes this entire pipeline into 24-48 hours."),
                ("Solving Missing Ground Proof",
                 "Senior leadership had no independent, rapid way to verify if riverbank boulder pitching or spurs were actually completed before releasing funds. Physical inspections were slow and paper-based."),
                ("Eliminating GFR 12-A Math Errors",
                 "Hand-drafted statutory Utilization Certificates frequently had arithmetic errors and unspent balance mismatches, causing recurring audit objections and stalled central grants."),
                ("Zero Files 'Lost in Transit'",
                 "Eliminates ambiguity over file status. Every stakeholder knows exactly where a proposal is and how many days it has been waiting.")
            ]
        },
        {
            "header_badge": "2. CORE OPERATIONAL MODULES",
            "header_col": C_BLUE,
            "title": "How the System Operates",
            "points": [
                ("Central Scheme Register (30 Schemes)",
                 "Immutable scheme codes (e.g. SK-01, AS-12) lock 90% Central Share and 10% State Share splits upon DPR approval, permanently linking all future bills and inspections."),
                ("3-Click Statutory GFR 12-A Engine",
                 "Automates Rule 238(1) compliance. Pre-fills historical grant balances, interest accrued, and verified expenditure; exports certified official Government of India PDFs with zero math errors."),
                ("Mandatory GPS Geotagged Audits",
                 "Site inspectors capture mobile riverbank photos embedded with camera GPS latitude, longitude, and timestamps. System verifies coordinates to block duplicate or stock photos."),
                ("Three-Tier Concurrence Workflow",
                 "State WRD submits claim → Brahmaputra Board technical review & photo verification → Ministry of Jal Shakti (MoJS) final release via PFMS.")
            ]
        },
        {
            "header_badge": "3. SENIOR EXECUTIVE GOVERNANCE",
            "header_col": C_GREEN,
            "title": "Value for Board Leadership",
            "points": [
                ("Interactive River Basin GIS Map",
                 "Visual satellite and terrain tracking across Teesta, Barak (Howrah, Tlawng, Jiri), Dhansiri, Lohit, Simsang, and Siang basins with 1-click status filters (Completed / Ongoing / Tendering)."),
                ("Executive Pendency Radar & Aging Clock",
                 "Instant visibility into whose desk each file is on (State WRD, Board Guwahati, or MoJS Delhi), with aging day counters and red-flag alerts for overdue claims."),
                ("100% Audit & Legal Protection",
                 "Complete immutable audit trail of every voucher, approval timestamp, and field inspection protects Board officials during Parliamentary and CAG audits."),
                ("One-Click Executive Briefing Reports",
                 "Generates instant summary spreadsheets and dossiers for review meetings with Ministry Secretaries and State Water Resources Ministers.")
            ]
        }
    ]

    for col_idx, col in enumerate(columns):
        col_x = x_start + col_idx * (w_col + gap_col)

        # Outer card container
        add_card(col_x, y_body, w_col, h_body, bg_col=C_WHITE, border_col=C_BORDER)

        # Top Accent Color Strip
        top_strip = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, col_x, y_body, w_col, Inches(0.06))
        top_strip.fill.solid()
        top_strip.fill.fore_color.rgb = col["header_col"]
        top_strip.line.fill.background()

        # Text Box
        ctx = slide.shapes.add_textbox(col_x + Inches(0.20), y_body + Inches(0.14), w_col - Inches(0.40), h_body - Inches(0.25))
        ctf = ctx.text_frame
        ctf.word_wrap = True
        ctf.margin_left = ctf.margin_top = ctf.margin_right = ctf.margin_bottom = 0

        # Header Badge
        p_badge = ctf.paragraphs[0]
        p_badge.text = col["header_badge"]
        p_badge.font.size = Pt(9)
        p_badge.font.bold = True
        p_badge.font.color.rgb = col["header_col"]

        # Section Title
        p_sec = ctf.add_paragraph()
        p_sec.text = col["title"]
        p_sec.font.size = Pt(14)
        p_sec.font.bold = True
        p_sec.font.color.rgb = C_DARK
        p_sec.space_before = Pt(2)

        # Content Points
        for h_txt, b_txt in col["points"]:
            p_head = ctf.add_paragraph()
            p_head.text = "• " + h_txt
            p_head.font.size = Pt(10)
            p_head.font.bold = True
            p_head.font.color.rgb = C_DARK
            p_head.space_before = Pt(6)

            p_body = ctf.add_paragraph()
            p_body.text = b_txt
            p_body.font.size = Pt(8.5)
            p_body.font.color.rgb = C_MUTED
            p_body.space_before = Pt(1)

    # =========================================================================
    # 5. Footer Strip (Deployment & Senior Authority Sign-Off)
    # =========================================================================
    y_foot = Inches(6.87)
    h_foot = Inches(0.48)
    w_foot = Inches(11.73)

    foot = add_card(Inches(0.8), y_foot, w_foot, h_foot, bg_col=C_NAVY, border_col=None)

    ftx = slide.shapes.add_textbox(Inches(0.95), y_foot + Inches(0.08), w_foot - Inches(0.3), Inches(0.32))
    ftf = ftx.text_frame
    ftf.word_wrap = True
    ftf.margin_left = ftf.margin_top = ftf.margin_right = ftf.margin_bottom = 0

    p_f = ftf.paragraphs[0]
    p_f.text = "LIVE CLOUD PORTAL:  https://103.162.247.169/dashboard   •   24/7 AUTO-RECOVERY WATCHDOG   •   ALL 30 APPROVED SCHEMES PRELOADED"
    p_f.font.size = Pt(9.5)
    p_f.font.bold = True
    p_f.font.color.rgb = C_GOLD
    p_f.alignment = PP_ALIGN.CENTER

    prs.save(output_file)
    print(f"Text-only single-page presentation created at: {output_file}")

if __name__ == "__main__":
    out_file = r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Single_Page_Summary.pptx"
    build_single_page_text_presentation(out_file)
    # Also overwrite FMBAP_Portal_Executive_OnePager.pptx so both files contain the exact single page requested!
    alt_out = r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Executive_OnePager.pptx"
    build_single_page_text_presentation(alt_out)
