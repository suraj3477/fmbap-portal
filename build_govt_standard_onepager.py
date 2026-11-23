import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE

def build_govt_presentation(output_file):
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # Official Government Color Palette (Clean, High Contrast, Authoritative)
    C_WHITE = RGBColor(255, 255, 255)
    C_NAVY = RGBColor(11, 37, 69)          # Deep Official Navy #0B2545
    C_BLUE = RGBColor(15, 76, 159)         # Govt Primary Blue #0F4C9F
    C_DARK = RGBColor(15, 23, 42)          # Primary Text #0F172A
    C_MUTED = RGBColor(51, 65, 85)         # Body Text #334155
    C_GOLD = RGBColor(180, 83, 9)          # Deep Gold #B45309
    C_GREEN = RGBColor(21, 128, 61)        # Forest Green #15803D
    C_LINE = RGBColor(203, 213, 225)       # Divider Line #CBD5E1

    slide = prs.slides.add_slide(blank_layout)

    # 1. Slide Background (Clean White)
    bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
    bg.fill.solid()
    bg.fill.fore_color.rgb = C_WHITE
    bg.line.fill.background()

    # 2. Government Header Block
    tx_head = slide.shapes.add_textbox(Inches(0.8), Inches(0.35), Inches(11.73), Inches(0.80))
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
    p_title.space_before = Pt(2)

    p_sub = tf_head.add_paragraph()
    p_sub.text = "Flood Management & Border Areas Programme  |  System Review for Senior Authority"
    p_sub.font.size = Pt(10.5)
    p_sub.font.color.rgb = C_MUTED
    p_sub.space_before = Pt(2)

    # Divider Line 1
    line1 = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.22), Inches(11.73), Inches(0.015))
    line1.fill.solid()
    line1.fill.fore_color.rgb = C_LINE
    line1.line.fill.background()

    # 3. Portfolio Summary Line (Clear & Bold)
    tx_kpi = slide.shapes.add_textbox(Inches(0.8), Inches(1.28), Inches(11.73), Inches(0.32))
    tf_kpi = tx_kpi.text_frame
    tf_kpi.word_wrap = True
    tf_kpi.margin_left = tf_kpi.margin_top = tf_kpi.margin_right = tf_kpi.margin_bottom = 0

    p_kpi = tf_kpi.paragraphs[0]
    r0 = p_kpi.add_run()
    r0.text = "PORTFOLIO STATUS:   "
    r0.font.bold = True
    r0.font.size = Pt(10)
    r0.font.color.rgb = C_DARK

    r1 = p_kpi.add_run()
    r1.text = "Approved Outlay: ₹3,740.81 Cr (30 Schemes • 90:10 Ratio)   |   "
    r1.font.bold = True
    r1.font.size = Pt(10)
    r1.font.color.rgb = C_BLUE

    r2 = p_kpi.add_run()
    r2.text = "Central Release: ₹205.47 Cr (Verified UCs)   |   "
    r2.font.bold = True
    r2.font.size = Pt(10)
    r2.font.color.rgb = C_GREEN

    r3 = p_kpi.add_run()
    r3.text = "Execution: 17 Completed • 5 Ongoing • 8 Tendering (66.2% Progress)"
    r3.font.bold = True
    r3.font.size = Pt(10)
    r3.font.color.rgb = C_NAVY

    # Divider Line 2
    line2 = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.64), Inches(11.73), Inches(0.015))
    line2.fill.solid()
    line2.fill.fore_color.rgb = C_LINE
    line2.line.fill.background()

    # =========================================================================
    # 4. Main Body: 2 Columns covering 1. Introduction, 2. Features, 3. Workflow, 4. Benefits
    # =========================================================================
    y_body = Inches(1.72)
    h_body = Inches(5.12)
    w_col = Inches(5.72)
    gap = Inches(0.29)

    # --- LEFT COLUMN: 1. INTRODUCTION & 2. FEATURES ---
    col1_x = Inches(0.8)
    tx_c1 = slide.shapes.add_textbox(col1_x, y_body, w_col, h_body)
    tf_c1 = tx_c1.text_frame
    tf_c1.word_wrap = True
    tf_c1.margin_left = tf_c1.margin_top = tf_c1.margin_right = tf_c1.margin_bottom = 0

    # Section 1: Introduction
    p_s1 = tf_c1.paragraphs[0]
    p_s1.text = "1. INTRODUCTION"
    p_s1.font.size = Pt(12.5)
    p_s1.font.bold = True
    p_s1.font.color.rgb = C_NAVY

    intro_points = [
        ("Institutional Mandate", "Central Sector Scheme implemented by Brahmaputra Board under Ministry of Jal Shakti for flood management and anti-erosion across North-Eastern states."),
        ("Problem Solved", "Replaces 60–90 day postal file movement between State capitals, Guwahati, and New Delhi with 24–48 hour digital processing."),
        ("Single Window", "Unifies DPR submissions, engineering inspections, statutory UCs, and fund releases on one central platform."),
        ("Operational Status", "Live and accessible at https://103.162.247.169/dashboard with all 30 approved scheme baselines preloaded.")
    ]
    for h, b in intro_points:
        p = tf_c1.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(9.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(3.5)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # Section 2: Features
    p_s2 = tf_c1.add_paragraph()
    p_s2.text = "2. KEY FEATURES"
    p_s2.font.size = Pt(12.5)
    p_s2.font.bold = True
    p_s2.font.color.rgb = C_NAVY
    p_s2.space_before = Pt(12)

    feature_points = [
        ("Scheme Baseline Register", "Codifies all 30 schemes with locked 90:10 funding splits, river tributaries, districts, and GPS bounds."),
        ("Form GFR 12-A Automation", "Pre-fills historical balances and auto-generates official statutory Utilization Certificates with zero math errors."),
        ("Geo-Tagged Mobile Audits", "Mandatory mobile riverbank photos with device GPS coordinates and timestamps; blocks duplicate or stock photos."),
        ("River Basin GIS Map", "Real-time spatial tracking across Teesta, Barak, Dhansiri, Lohit, Simsang, and Siang basins with satellite views."),
        ("File Pendency Radar", "Live desk tracking (State WRD / Board / MoJS) with aging counters and red alerts for overdue files.")
    ]
    for h, b in feature_points:
        p = tf_c1.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(9.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(3.5)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # --- RIGHT COLUMN: 3. WORKFLOW & 4. BENEFITS ---
    col2_x = Inches(6.81)
    tx_c2 = slide.shapes.add_textbox(col2_x, y_body, w_col, h_body)
    tf_c2 = tx_c2.text_frame
    tf_c2.word_wrap = True
    tf_c2.margin_left = tf_c2.margin_top = tf_c2.margin_right = tf_c2.margin_bottom = 0

    # Section 3: Workflow
    p_s3 = tf_c2.paragraphs[0]
    p_s3.text = "3. OPERATIONAL WORKFLOW"
    p_s3.font.size = Pt(12.5)
    p_s3.font.bold = True
    p_s3.font.color.rgb = C_NAVY

    workflow_points = [
        ("Step 1: State WRD Submission", "State department logs in, uploads DPR and measurement vouchers, and requisitions funds against sanctioned baselines."),
        ("Step 2: Board Technical Audit", "Brahmaputra Board engineers visit site, verify works with GPS photos, review bill vouchers, and recommend release."),
        ("Step 3: GFR 12-A Verification", "Portal auto-generates statutory Form GFR 12-A, ensuring claimed money strictly matches verified physical progress."),
        ("Step 4: Ministry Sanction & PFMS", "Ministry of Jal Shakti accords final administrative sanction and enters PFMS reference for direct fund transfer.")
    ]
    for h, b in workflow_points:
        p = tf_c2.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(9.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(3.5)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # Section 4: Benefits
    p_s4 = tf_c2.add_paragraph()
    p_s4.text = "4. STRATEGIC BENEFITS"
    p_s4.font.size = Pt(12.5)
    p_s4.font.bold = True
    p_s4.font.color.rgb = C_NAVY
    p_s4.space_before = Pt(12)

    benefit_points = [
        ("Rapid Processing & Speed", "Reduces file processing cycles from 60–90 days to 24–48 hours; eliminates files 'lost in transit'."),
        ("Audit & Legal Protection", "Every released rupee is backed by GPS-stamped photo proof and verified UCs, protecting against CAG audit objections."),
        ("Zero Arithmetic Errors", "Automated financial sizing algorithms prevent math mistakes and block claims exceeding physical work."),
        ("Leadership Oversight", "Provides Chairman and Board Members total operational command via live river maps, pendency alerts, and 1-click reports.")
    ]
    for h, b in benefit_points:
        p = tf_c2.add_paragraph()
        p.text = "• " + h + ": "
        p.font.size = Pt(9.5)
        p.font.bold = True
        p.font.color.rgb = C_DARK
        p.space_before = Pt(3.5)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # Divider Line 3
    line3 = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(6.90), Inches(11.73), Inches(0.015))
    line3.fill.solid()
    line3.fill.fore_color.rgb = C_LINE
    line3.line.fill.background()

    # 5. Clean Footer Text
    tx_foot = slide.shapes.add_textbox(Inches(0.8), Inches(6.96), Inches(11.73), Inches(0.35))
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
    print(f"Presentation saved cleanly at: {output_file}")

if __name__ == "__main__":
    out_files = [
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Single_Page_Summary.pptx",
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Executive_OnePager.pptx"
    ]
    for f in out_files:
        try:
            build_govt_presentation(f)
        except PermissionError:
            print(f"Skipping {f} (currently open in another application)")
