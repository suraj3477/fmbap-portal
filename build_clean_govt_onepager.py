import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE

def build_clean_presentation(output_file):
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # Clean Government Palette
    C_WHITE = RGBColor(255, 255, 255)
    C_NAVY = RGBColor(11, 37, 69)          # Deep Official Navy #0B2545
    C_BLUE = RGBColor(15, 76, 159)         # Govt Primary Blue #0F4C9F
    C_DARK = RGBColor(15, 23, 42)          # Primary Body Text #0F172A
    C_MUTED = RGBColor(51, 65, 85)         # Body Text #334155
    C_LINE = RGBColor(203, 213, 225)       # Divider Line #CBD5E1
    C_GOLD = RGBColor(180, 83, 9)          # Deep Gold #B45309
    C_GREEN = RGBColor(21, 128, 61)        # Forest Green #15803D

    slide = prs.slides.add_slide(blank_layout)

    # 1. Slide Background (Clean White)
    bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
    bg.fill.solid()
    bg.fill.fore_color.rgb = C_WHITE
    bg.line.fill.background()

    # 2. Header Block
    tx_head = slide.shapes.add_textbox(Inches(0.8), Inches(0.40), Inches(11.73), Inches(0.85))
    tf_head = tx_head.text_frame
    tf_head.word_wrap = True
    tf_head.margin_left = tf_head.margin_top = tf_head.margin_right = tf_head.margin_bottom = 0

    p_org = tf_head.paragraphs[0]
    p_org.text = "GOVERNMENT OF INDIA  •  MINISTRY OF JAL SHAKTI  •  BRAHMAPUTRA BOARD"
    p_org.font.size = Pt(10)
    p_org.font.bold = True
    p_org.font.color.rgb = C_GOLD

    p_title = tf_head.add_paragraph()
    p_title.text = "FMBAP Digital Monitoring Portal — Executive Summary"
    p_title.font.size = Pt(22)
    p_title.font.bold = True
    p_title.font.color.rgb = C_NAVY
    p_title.space_before = Pt(3)

    p_sub = tf_head.add_paragraph()
    p_sub.text = "Flood Management & Border Areas Programme  |  Senior Authority Review"
    p_sub.font.size = Pt(10.5)
    p_sub.font.color.rgb = C_MUTED
    p_sub.space_before = Pt(2)

    # Divider Line 1
    line1 = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.30), Inches(11.73), Inches(0.015))
    line1.fill.solid()
    line1.fill.fore_color.rgb = C_LINE
    line1.line.fill.background()

    # 3. Key Portfolio Figures
    tx_kpi = slide.shapes.add_textbox(Inches(0.8), Inches(1.36), Inches(11.73), Inches(0.35))
    tf_kpi = tx_kpi.text_frame
    tf_kpi.word_wrap = True
    tf_kpi.margin_left = tf_kpi.margin_top = tf_kpi.margin_right = tf_kpi.margin_bottom = 0

    p_kpi = tf_kpi.paragraphs[0]
    r0 = p_kpi.add_run()
    r0.text = "PORTFOLIO STATUS:   "
    r0.font.bold = True
    r0.font.size = Pt(10.5)
    r0.font.color.rgb = C_DARK

    r1 = p_kpi.add_run()
    r1.text = "Sanctioned Outlay: ₹3,740.81 Cr (30 Approved Schemes)   |   "
    r1.font.bold = True
    r1.font.size = Pt(10.5)
    r1.font.color.rgb = C_BLUE

    r2 = p_kpi.add_run()
    r2.text = "Central Released: ₹205.47 Cr (Verified UCs)   |   "
    r2.font.bold = True
    r2.font.size = Pt(10.5)
    r2.font.color.rgb = C_GREEN

    r3 = p_kpi.add_run()
    r3.text = "Execution: 17 Done • 5 Active • 8 Tendering (66.2% Progress)"
    r3.font.bold = True
    r3.font.size = Pt(10.5)
    r3.font.color.rgb = C_NAVY

    # Divider Line 2
    line2 = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.74), Inches(11.73), Inches(0.015))
    line2.fill.solid()
    line2.fill.fore_color.rgb = C_LINE
    line2.line.fill.background()

    # =========================================================================
    # 4. Main Body: 2 Columns covering 1. Introduction, 2. Features, 3. Workflow, 4. Benefits
    # Only Crucial Information from the Portal — Simple, Clean, High Impact
    # =========================================================================
    y_body = Inches(1.85)
    h_body = Inches(4.90)
    w_col = Inches(5.72)

    # --- LEFT COLUMN: 1. INTRODUCTION & 2. KEY FEATURES ---
    col1_x = Inches(0.8)
    tx_c1 = slide.shapes.add_textbox(col1_x, y_body, w_col, h_body)
    tf_c1 = tx_c1.text_frame
    tf_c1.word_wrap = True
    tf_c1.margin_left = tf_c1.margin_top = tf_c1.margin_right = tf_c1.margin_bottom = 0

    # Section 1: Introduction
    p_s1 = tf_c1.paragraphs[0]
    p_s1.text = "1. INTRODUCTION"
    p_s1.font.size = Pt(13)
    p_s1.font.bold = True
    p_s1.font.color.rgb = C_NAVY

    intro_points = [
        ("Mandate", "Central Sector Scheme monitoring 30 flood management and anti-erosion works under Ministry of Jal Shakti across North-Eastern states."),
        ("Funding Patterns", "Supports all Govt patterns: 90:10 (NE States), 60:40 / 50:50 (General States), and 100:0 (Border RMBA works)."),
        ("Problem Solved", "Replaces physical paper file movement and manual registers with an integrated, paperless digital scrutiny and approval workflow."),
        ("Live Deployment", "Production portal is live at https://103.162.247.169/dashboard with all 30 schemes preloaded.")
    ]
    for h, b in intro_points:
        p = tf_c1.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(10)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(4.5)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # Section 2: Features
    p_s2 = tf_c1.add_paragraph()
    p_s2.text = "2. KEY FEATURES"
    p_s2.font.size = Pt(13)
    p_s2.font.bold = True
    p_s2.font.color.rgb = C_NAVY
    p_s2.space_before = Pt(14)

    feature_points = [
        ("Schemes Catalogue", "Codified register of all 30 schemes with auto-calculated funding splits (90:10, 60:40, 100:0), tributaries, and GPS bounds."),
        ("Form GFR 12-A Engine", "Auto-calculates grant balances and generates statutory Utilization Certificate PDFs with zero arithmetic errors."),
        ("Geo-Tagged Site Audits", "Mandatory mobile riverbank photos with device GPS coordinates and timestamps; blocks duplicate or stock photos."),
        ("River Basin GIS Map", "Interactive spatial tracking across Teesta, Barak, Dhansiri, Lohit, Simsang & Siang basins with satellite views."),
        ("File Pendency Radar", "Live desk-level tracking (State WRD / Board / MoJS) with aging counters and red alerts for overdue files.")
    ]
    for h, b in feature_points:
        p = tf_c1.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(10)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(4.5)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # --- RIGHT COLUMN: 3. OPERATIONAL WORKFLOW & 4. STRATEGIC BENEFITS ---
    col2_x = Inches(6.81)
    tx_c2 = slide.shapes.add_textbox(col2_x, y_body, w_col, h_body)
    tf_c2 = tx_c2.text_frame
    tf_c2.word_wrap = True
    tf_c2.margin_left = tf_c2.margin_top = tf_c2.margin_right = tf_c2.margin_bottom = 0

    # Section 3: Workflow
    p_s3 = tf_c2.paragraphs[0]
    p_s3.text = "3. OPERATIONAL WORKFLOW"
    p_s3.font.size = Pt(13)
    p_s3.font.bold = True
    p_s3.font.color.rgb = C_NAVY

    workflow_points = [
        ("1. State WRD Submission", "State department logs in, uploads DPRs, bill vouchers, and requisitions funds against sanctioned baselines."),
        ("2. Board Technical Audit", "Brahmaputra Board engineers inspect river site, verify works with GPS photos, and recommend release."),
        ("3. GFR 12-A Verification", "Portal auto-generates Form GFR 12-A, cross-checking claimed money against verified on-ground work."),
        ("4. Ministry Sanction & PFMS", "Ministry of Jal Shakti accords final financial sanction and enters PFMS reference for direct fund transfer.")
    ]
    for h, b in workflow_points:
        p = tf_c2.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(10)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(4.5)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # Section 4: Benefits
    p_s4 = tf_c2.add_paragraph()
    p_s4.text = "4. STRATEGIC BENEFITS"
    p_s4.font.size = Pt(13)
    p_s4.font.bold = True
    p_s4.font.color.rgb = C_NAVY
    p_s4.space_before = Pt(14)

    benefit_points = [
        ("Streamlined Processing", "Paperless file movement eliminates physical courier delays and completely prevents files from getting lost in transit."),
        ("Audit Compliance", "100% tamper-proof records with GPS photos protect officials against CAG audit objections."),
        ("Financial Accuracy", "Automated calculations prevent math mismatches and block claims exceeding physical work."),
        ("Executive Oversight", "Provides Board Leadership instant command via live GIS tracking, pendency alerts, and 1-click reports.")
    ]
    for h, b in benefit_points:
        p = tf_c2.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(10)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(4.5)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # Divider Line 3
    line3 = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(6.85), Inches(11.73), Inches(0.015))
    line3.fill.solid()
    line3.fill.fore_color.rgb = C_LINE
    line3.line.fill.background()

    # 5. Clean Footer Text
    tx_foot = slide.shapes.add_textbox(Inches(0.8), Inches(6.92), Inches(11.73), Inches(0.35))
    tf_foot = tx_foot.text_frame
    tf_foot.word_wrap = True
    tf_foot.margin_left = tf_foot.margin_top = tf_foot.margin_right = tf_foot.margin_bottom = 0

    p_foot = tf_foot.paragraphs[0]
    p_foot.text = "PRODUCTION URL:  https://103.162.247.169/dashboard   |   24/7 AUTO-RECOVERY WATCHDOG   |   ALL 30 SCHEMES PRELOADED"
    p_foot.font.size = Pt(9.5)
    p_foot.font.bold = True
    p_foot.font.color.rgb = C_BLUE
    p_foot.alignment = PP_ALIGN.CENTER

    prs.save(output_file)
    print(f"Clean presentation saved at: {output_file}")

if __name__ == "__main__":
    out_files = [
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Executive_Summary_Govt.pptx",
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Executive_OnePager.pptx",
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Single_Page_Summary.pptx"
    ]
    for f in out_files:
        try:
            build_clean_presentation(f)
        except PermissionError:
            print(f"Skipping {f} (file locked/open)")
