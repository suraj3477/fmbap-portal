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

    # Minimalist Executive Palette (Strictly Clean Text, No Graphics/Boxes)
    C_WHITE = RGBColor(255, 255, 255)
    C_NAVY = RGBColor(11, 37, 69)          # Deep Official Navy #0B2545
    C_BLUE = RGBColor(15, 76, 159)         # Govt Primary Blue #0F4C9F
    C_DARK = RGBColor(15, 23, 42)          # Primary Body Charcoal #0F172A
    C_MUTED = RGBColor(51, 65, 85)         # Secondary Slate #334155
    C_LINE = RGBColor(203, 213, 225)       # Divider Slate #CBD5E1
    C_GOLD = RGBColor(180, 83, 9)          # Dark Gold #B45309
    C_GREEN = RGBColor(21, 128, 61)        # Forest Green #15803D

    slide = prs.slides.add_slide(blank_layout)

    # 1. Plain White Background
    bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
    bg.fill.solid()
    bg.fill.fore_color.rgb = C_WHITE
    bg.line.fill.background()

    # 2. Header (Plain Clean Text - No Background Box)
    tx_head = slide.shapes.add_textbox(Inches(0.8), Inches(0.32), Inches(11.73), Inches(0.80))
    tf_head = tx_head.text_frame
    tf_head.word_wrap = True
    tf_head.margin_left = tf_head.margin_top = tf_head.margin_right = tf_head.margin_bottom = 0

    p_org = tf_head.paragraphs[0]
    p_org.text = "GOVERNMENT OF INDIA  •  MINISTRY OF JAL SHAKTI  •  BRAHMAPUTRA BOARD"
    p_org.font.size = Pt(9.5)
    p_org.font.bold = True
    p_org.font.color.rgb = C_GOLD

    p_title = tf_head.add_paragraph()
    p_title.text = "Flood Management & Border Areas Programme (FMBAP) Monitoring Portal"
    p_title.font.size = Pt(20)
    p_title.font.bold = True
    p_title.font.color.rgb = C_NAVY

    p_sub = tf_head.add_paragraph()
    p_sub.text = "Executive Briefing for Senior Leadership Review  |  Introduction, Features, Workflow & Benefits"
    p_sub.font.size = Pt(9.5)
    p_sub.font.color.rgb = C_MUTED

    # Thin Divider Line 1
    line1 = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.18), Inches(11.73), Inches(0.015))
    line1.fill.solid()
    line1.fill.fore_color.rgb = C_LINE
    line1.line.fill.background()

    # 3. Master Financial & Execution Metrics Strip
    tx_kpi = slide.shapes.add_textbox(Inches(0.8), Inches(1.24), Inches(11.73), Inches(0.32))
    tf_kpi = tx_kpi.text_frame
    tf_kpi.word_wrap = True
    tf_kpi.margin_left = tf_kpi.margin_top = tf_kpi.margin_right = tf_kpi.margin_bottom = 0

    p_kpi = tf_kpi.paragraphs[0]
    p_kpi.text = "PORTFOLIO SCALE:   "
    p_kpi.font.size = Pt(9.5)
    p_kpi.font.bold = True
    p_kpi.font.color.rgb = C_DARK

    run1 = p_kpi.add_run()
    run1.text = "Approved Outlay: ₹3,740.81 Cr (30 Schemes • 90% Central : 10% State)   |   "
    run1.font.bold = True
    run1.font.color.rgb = C_BLUE

    run2 = p_kpi.add_run()
    run2.text = "Central Release: ₹205.47 Cr (100% GFR 12-A Matched)   |   "
    run2.font.bold = True
    run2.font.color.rgb = C_GREEN

    run3 = p_kpi.add_run()
    run3.text = "Status: 17 Done • 5 Active • 8 Tendering (66.2% Avg Progress)"
    run3.font.bold = True
    run3.font.color.rgb = C_NAVY

    # Thin Divider Line 2
    line2 = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.60), Inches(11.73), Inches(0.015))
    line2.fill.solid()
    line2.fill.fore_color.rgb = C_LINE
    line2.line.fill.background()

    # =========================================================================
    # 4. Main Body: 2 Columns covering 1. Introduction, 2. Features, 3. Workflow, 4. Benefits
    # =========================================================================
    y_body = Inches(1.68)
    h_body = Inches(5.15)
    w_col = Inches(5.72)
    gap = Inches(0.29)

    # --- LEFT COLUMN: 1. INTRODUCTION & 2. FEATURES ---
    col1_x = Inches(0.8)
    tx_c1 = slide.shapes.add_textbox(col1_x, y_body, w_col, h_body)
    tf_c1 = tx_c1.text_frame
    tf_c1.word_wrap = True
    tf_c1.margin_left = tf_c1.margin_top = tf_c1.margin_right = tf_c1.margin_bottom = 0

    col1_content = [
        ("1. INTRODUCTION", [
            ("Institutional Mandate", "Central Sector Scheme executed by Brahmaputra Board under Ministry of Jal Shakti across North-Eastern states to systematically control recurring river floods, severe bank erosion, and siltation."),
            ("Problem Solved", "Replaces slow 60-90 day physical paper file circulation between State WRDs, Guwahati, and New Delhi with real-time digital concurrence completed in 24-48 hours."),
            ("Single National Window", "Unifies DPR submissions, engineering site inspections, statutory GFR 12-A Utilization Certificates, and fund disbursal tracking onto one centralized digital platform."),
            ("Live Production Status", "Fully operational today at https://103.162.247.169/dashboard on an enterprise 8 vCPU / 16 GB Cloud VM with 24/7 auto-recovery watchdog; preloaded with all 30 baseline schemes.")
        ]),
        ("2. KEY FEATURES", [
            ("Central Scheme Baseline Register", "Codifies all 30 schemes with unique IDs (SK-01, AS-12), locking the approved 90% Central and 10% State funding splits, river tributaries, districts, and GPS boundary bounds."),
            ("3-Click Statutory GFR 12-A Engine", "Automates Rule 238(1) compliance. Pre-fills historical grant releases, interest, and verified expenditure; exports certified official Government of India PDFs with zero arithmetic errors."),
            ("Geotagged Mobile Field Audits", "Mandatory mobile riverbank photos embedded with camera GPS latitude, longitude, and timestamps; validates physical boulder revetments/spurs and blocks duplicate or stock photos."),
            ("Interactive River Basin GIS Map", "Full spatial tracking across Teesta, Barak (Howrah, Tlawng, Jiri), Dhansiri, Lohit, Simsang, and Siang basins with satellite/terrain views and 1-click status-filtered pins."),
            ("Executive Pendency Radar & Aging Clock", "Real-time visibility into whose desk each file is on (State WRD, Board Guwahati, or MoJS Delhi) with an aging days counter, overdue red flags, and 1-click Excel export.")
        ])
    ]

    first_c1 = True
    for sec_title, items in col1_content:
        if first_c1:
            p = tf_c1.paragraphs[0]
            first_c1 = False
        else:
            p = tf_c1.add_paragraph()
            p.space_before = Pt(8)
        p.text = sec_title
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = C_NAVY

        for item_h, item_b in items:
            p_i = tf_c1.add_paragraph()
            p_i.text = "• " + item_h + ": "
            p_i.font.size = Pt(8.5)
            p_i.font.bold = True
            p_i.font.color.rgb = C_DARK
            p_i.space_before = Pt(2.5)

            run = p_i.add_run()
            run.text = item_b
            run.font.bold = False
            run.font.color.rgb = C_MUTED

    # --- RIGHT COLUMN: 3. WORKFLOW & 4. BENEFITS ---
    col2_x = Inches(6.81)
    tx_c2 = slide.shapes.add_textbox(col2_x, y_body, w_col, h_body)
    tf_c2 = tx_c2.text_frame
    tf_c2.word_wrap = True
    tf_c2.margin_left = tf_c2.margin_top = tf_c2.margin_right = tf_c2.margin_bottom = 0

    col2_content = [
        ("3. OPERATIONAL WORKFLOW", [
            ("Step 1: State WRD Proposal & Requisition", "State department officers log in, upload detailed project reports (DPRs), enter contractor measurement vouchers, and claim fund installments against approved baselines."),
            ("Step 2: Brahmaputra Board Site Inspection", "Board field engineers visit riverbank sites, capture GPS-stamped geotagged photos of completed spurs and boulder pitching, verify work quality, and record technical recommendations."),
            ("Step 3: Statutory GFR 12-A Certification", "System auto-generates Form GFR 12-A. State and Board officers verify that claimed money strictly matches physical completion, pre-empting audit discrepancies before submission."),
            ("Step 4: Ministry Sanction & PFMS Disbursal", "Ministry of Jal Shakti (MoJS) reviews the verified digital dossier, accords administrative approval, and logs the PFMS transaction release reference for direct fund credit.")
        ]),
        ("4. STRATEGIC BENEFITS", [
            ("100% Digital Transparency & Speed", "Compresses file transit from 60-90 days to 24-48 hours. Completely eliminates communication gaps and files 'lost in transit' between State capitals and New Delhi."),
            ("Protection Against CAG Audit Objections", "Every rupee disbursed is backed by certified contractor bills, statutory Form GFR 12-A UCs, and tamper-proof GPS photos, providing foolproof legal and audit defence."),
            ("Zero Arithmetic Errors & Over-Claims", "Automated financial sizing algorithms prevent calculation mistakes and automatically block payment recommendations if physical ground work lags behind claimed funds."),
            ("Real-Time Command for Board Leadership", "Chairman, Member Technical, and Chief Engineers have complete command through live river basin GIS tracking, file aging alerts, and instant 1-click meeting briefing sheets.")
        ])
    ]

    first_c2 = True
    for sec_title, items in col2_content:
        if first_c2:
            p = tf_c2.paragraphs[0]
            first_c2 = False
        else:
            p = tf_c2.add_paragraph()
            p.space_before = Pt(8)
        p.text = sec_title
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = C_NAVY

        for item_h, item_b in items:
            p_i = tf_c2.add_paragraph()
            p_i.text = "• " + item_h + ": "
            p_i.font.size = Pt(8.5)
            p_i.font.bold = True
            p_i.font.color.rgb = C_DARK
            p_i.space_before = Pt(2.5)

            run = p_i.add_run()
            run.text = item_b
            run.font.bold = False
            run.font.color.rgb = C_MUTED

    # Thin Divider Line 3
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
    p_foot.text = "LIVE CLOUD ADDRESS:  https://103.162.247.169/dashboard   |   24/7 AUTO-RECOVERY WATCHDOG   |   ALL 30 SCHEMES & GIS DATA PRELOADED"
    p_foot.font.size = Pt(9)
    p_foot.font.bold = True
    p_foot.font.color.rgb = C_BLUE
    p_foot.alignment = PP_ALIGN.CENTER

    prs.save(output_file)
    print(f"Presentation saved successfully at: {output_file}")

if __name__ == "__main__":
    out_files = [
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Single_Page_Summary.pptx",
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Executive_OnePager.pptx"
    ]
    for f in out_files:
        build_presentation(f)
