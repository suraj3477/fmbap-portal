import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE

def build_minimal_presentation(output_file):
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # Minimalist Clean Executive Palette (Pure Text, No Cards, No Extra Lines)
    C_WHITE = RGBColor(255, 255, 255)
    C_NAVY = RGBColor(11, 37, 69)          # Deep Official Navy #0B2545
    C_BLUE = RGBColor(15, 76, 159)         # Govt Primary Blue #0F4C9F
    C_DARK = RGBColor(15, 23, 42)          # Primary Body Text #0F172A
    C_MUTED = RGBColor(71, 85, 105)        # Body Text Slate #475569
    C_GOLD = RGBColor(180, 83, 9)          # Deep Gold #B45309
    C_GREEN = RGBColor(21, 128, 61)        # Forest Green #15803D
    C_AMBER = RGBColor(194, 65, 12)        # Warm Amber / Rust #C2410C

    slide = prs.slides.add_slide(blank_layout)

    # 1. Slide Background (Clean White - No boxes)
    bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
    bg.fill.solid()
    bg.fill.fore_color.rgb = C_WHITE
    bg.line.fill.background()

    # 2. Header Block (Plain Text - No Box)
    tx_head = slide.shapes.add_textbox(Inches(0.8), Inches(0.40), Inches(11.73), Inches(0.85))
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

    # 3. Master Portfolio Status Line (Plain Clean Text - No Card Box, No Border)
    tx_kpi = slide.shapes.add_textbox(Inches(0.8), Inches(1.30), Inches(11.73), Inches(0.35))
    tf_kpi = tx_kpi.text_frame
    tf_kpi.word_wrap = True
    tf_kpi.margin_left = tf_kpi.margin_top = tf_kpi.margin_right = tf_kpi.margin_bottom = 0

    p_kpi = tf_kpi.paragraphs[0]
    p_kpi.alignment = PP_ALIGN.LEFT

    r0 = p_kpi.add_run()
    r0.text = "MASTER PORTFOLIO:   "
    r0.font.bold = True
    r0.font.size = Pt(9.5)
    r0.font.color.rgb = C_DARK

    r1 = p_kpi.add_run()
    r1.text = "Sanctioned Outlay: ₹3,740.81 Cr (30 Schemes)   •   "
    r1.font.bold = True
    r1.font.size = Pt(9.5)
    r1.font.color.rgb = C_BLUE

    r2 = p_kpi.add_run()
    r2.text = "Central Released: ₹205.47 Cr (Verified UCs)   •   "
    r2.font.bold = True
    r2.font.size = Pt(9.5)
    r2.font.color.rgb = C_GREEN

    r3 = p_kpi.add_run()
    r3.text = "Status: 17 Completed, 5 Ongoing, 8 Tendering (66.2% Avg Progress)"
    r3.font.bold = True
    r3.font.size = Pt(9.5)
    r3.font.color.rgb = C_NAVY

    # =========================================================================
    # 4. Main Body: 2 Clean Text Columns (No Cards, No Lines)
    # =========================================================================
    col_w = Inches(5.72)
    col1_x = Inches(0.8)
    col2_x = Inches(6.81)
    y_body = Inches(1.75)
    h_body = Inches(5.05)

    # --- LEFT COLUMN: 1. INTRODUCTION & 2. KEY FEATURES ---
    tx_c1 = slide.shapes.add_textbox(col1_x, y_body, col_w, h_body)
    tf_c1 = tx_c1.text_frame
    tf_c1.word_wrap = True
    tf_c1.margin_left = tf_c1.margin_top = tf_c1.margin_right = tf_c1.margin_bottom = 0

    # Section 1: Introduction
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
        p.space_before = Pt(4)

        run = p.add_run()
        run.text = b
        run.font.bold = False
        run.font.color.rgb = C_MUTED

    # Section 2: Features
    p_s2 = tf_c1.add_paragraph()
    p_s2.text = "2. KEY FEATURES (Core Capabilities)"
    p_s2.font.size = Pt(12)
    p_s2.font.bold = True
    p_s2.font.color.rgb = C_BLUE
    p_s2.space_before = Pt(14)

    s2_items = [
        ("Scheme Master Directory", "Unique codification (SK-01, AS-12) permanently linking approved outlays, state/central shares, tributaries, and GPS bounds."),
        ("GFR 12-A Statutory Engine", "3-click Utilization Certificate generation; auto-calculates unspent balances and produces official verified PDFs."),
        ("Geo-Tagged Site Inspections", "Mandatory mobile riverbank photos with device GPS latitude, longitude, and timestamps; blocks duplicate or stock photos."),
        ("Interactive GIS Basin Map", "Satellite and terrain tracking across Teesta, Barak, Dhansiri, Lohit, Simsang & Siang basins with status pins."),
        ("File Pendency Radar", "Desk-level custody tracking (State WRD / Board / MoJS) with aging clocks and red-flag alerts for overdue claims.")
    ]
    for h, b in s2_items:
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

    # --- RIGHT COLUMN: 3. OPERATIONAL WORKFLOW & 4. STRATEGIC BENEFITS ---
    tx_c2 = slide.shapes.add_textbox(col2_x, y_body, col_w, h_body)
    tf_c2 = tx_c2.text_frame
    tf_c2.word_wrap = True
    tf_c2.margin_left = tf_c2.margin_top = tf_c2.margin_right = tf_c2.margin_bottom = 0

    # Section 3: Workflow
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
        ("Step 5: Executive MIS & Reporting", "System auto-compiles national dashboards, pendency aging summaries, and generates 1-click Excel/PDF review dossiers.")
    ]
    for h, b in s3_items:
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
    p_s4.text = "4. STRATEGIC BENEFITS (Governance Impact)"
    p_s4.font.size = Pt(12)
    p_s4.font.bold = True
    p_s4.font.color.rgb = C_AMBER
    p_s4.space_before = Pt(14)

    s4_items = [
        ("Total Administrative Accountability", "Replaces physical paper files with paperless digital tracking; eliminates files getting lost in transit across departments."),
        ("CAG Audit & Legal Defense", "Every disbursed rupee is backed by immutable digital records, contractor vouchers, and GPS-stamped photo logs."),
        ("Financial Discipline", "Enforces physical-before-financial release; automated formulas prevent arithmetic mismatches and over-allocation."),
        ("Real-Time Leadership Control", "Chairman, Member Technical, and Chief Engineers have complete command over all river basins and file aging.")
    ]
    for h, b in s4_items:
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

    # 5. Clean Footer (Plain Text Line - No Card Box, No Border)
    tx_foot = slide.shapes.add_textbox(Inches(0.8), Inches(6.92), Inches(11.73), Inches(0.35))
    tf_foot = tx_foot.text_frame
    tf_foot.word_wrap = True
    tf_foot.margin_left = tf_foot.margin_top = tf_foot.margin_right = tf_foot.margin_bottom = 0

    p_foot = tf_foot.paragraphs[0]
    p_foot.text = "PRODUCTION URL:  https://103.162.247.169/dashboard   |   24/7 AUTO-RECOVERY WATCHDOG   |   ALL 30 SCHEMES PRELOADED"
    p_foot.font.size = Pt(9)
    p_foot.font.bold = True
    p_foot.font.color.rgb = C_BLUE
    p_foot.alignment = PP_ALIGN.CENTER

    prs.save(output_file)
    print(f"Minimal presentation saved at: {output_file}")

if __name__ == "__main__":
    out_files = [
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Single_Page_Summary.pptx",
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Executive_Summary_Govt.pptx",
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Executive_OnePager.pptx"
    ]
    for f in out_files:
        try:
            build_minimal_presentation(f)
        except PermissionError:
            print(f"Skipping {f} (file locked/open)")
