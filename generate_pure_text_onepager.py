import os
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN
from pptx.enum.shapes import MSO_SHAPE

def build_pure_text_onepager(output_file):
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    # Minimalist Executive Palette (Clean White, Deep Navy, Charcoal & Muted Slate)
    C_WHITE = RGBColor(255, 255, 255)
    C_NAVY = RGBColor(11, 37, 69)          # Deep Navy #0B2545
    C_BLUE = RGBColor(15, 76, 159)         # Govt Primary Blue #0F4C9F
    C_DARK = RGBColor(15, 23, 42)          # Primary Body Charcoal #0F172A
    C_MUTED = RGBColor(71, 85, 105)        # Secondary Slate #475569
    C_LINE = RGBColor(203, 213, 225)       # Divider Slate #CBD5E1
    C_GOLD = RGBColor(180, 83, 9)          # Dark Gold #B45309
    C_GREEN = RGBColor(21, 128, 61)        # Forest Green #15803D

    slide = prs.slides.add_slide(blank_layout)

    # 1. Clean White Background
    bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, prs.slide_width, prs.slide_height)
    bg.fill.solid()
    bg.fill.fore_color.rgb = C_WHITE
    bg.line.fill.background()

    # 2. Header (Plain Clean Text - No Box)
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
    p_title.text = "Flood Management & Border Areas Programme (FMBAP) Monitoring Portal"
    p_title.font.size = Pt(21)
    p_title.font.bold = True
    p_title.font.color.rgb = C_NAVY

    p_sub = tf_head.add_paragraph()
    p_sub.text = "Executive Briefing for Senior Authority Review  |  System Architecture, Governance Modules & Operational Status"
    p_sub.font.size = Pt(10)
    p_sub.font.color.rgb = C_MUTED

    # Thin Divider Line
    line1 = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.22), Inches(11.73), Inches(0.015))
    line1.fill.solid()
    line1.fill.fore_color.rgb = C_LINE
    line1.line.fill.background()

    # 3. Master Financial & Physical Portfolio Strip (Clean Inline Text)
    tx_kpi = slide.shapes.add_textbox(Inches(0.8), Inches(1.28), Inches(11.73), Inches(0.35))
    tf_kpi = tx_kpi.text_frame
    tf_kpi.word_wrap = True
    tf_kpi.margin_left = tf_kpi.margin_top = tf_kpi.margin_right = tf_kpi.margin_bottom = 0

    p_kpi = tf_kpi.paragraphs[0]
    p_kpi.text = "LIVE PORTFOLIO METRICS:   "
    p_kpi.font.size = Pt(9.5)
    p_kpi.font.bold = True
    p_kpi.font.color.rgb = C_DARK

    run1 = p_kpi.add_run()
    run1.text = "Sanctioned Outlay: ₹3,740.81 Cr (30 Schemes • 90% Central : 10% State)   |   "
    run1.font.bold = True
    run1.font.color.rgb = C_BLUE

    run2 = p_kpi.add_run()
    run2.text = "Central Release: ₹205.47 Cr (100% GFR 12-A Matched)   |   "
    run2.font.bold = True
    run2.font.color.rgb = C_GREEN

    run3 = p_kpi.add_run()
    run3.text = "Execution: 17 Done • 5 Active • 8 Tendering (66.2% Avg Progress)"
    run3.font.bold = True
    run3.font.color.rgb = C_NAVY

    # Thin Divider Line 2
    line2 = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(1.68), Inches(11.73), Inches(0.015))
    line2.fill.solid()
    line2.fill.fore_color.rgb = C_LINE
    line2.line.fill.background()

    # =========================================================================
    # 4. Main Body: Two Balanced Text Columns
    # =========================================================================
    y_body = Inches(1.76)
    h_body = Inches(5.05)
    w_col = Inches(5.72)
    gap = Inches(0.29)

    # --- LEFT COLUMN ---
    col1_x = Inches(0.8)
    tx_c1 = slide.shapes.add_textbox(col1_x, y_body, w_col, h_body)
    tf_c1 = tx_c1.text_frame
    tf_c1.word_wrap = True
    tf_c1.margin_left = tf_c1.margin_top = tf_c1.margin_right = tf_c1.margin_bottom = 0

    col1_sections = [
        ("1. MANDATE & PROBLEM CONTEXT (WHY BUILT)", [
            ("Ending 60-90 Day Paper Routing", "Proposals, bills, and engineering sanctions previously moved via physical courier between State WRD capitals, Guwahati, and New Delhi. The portal digitizes this entire workflow into 24-48 hours."),
            ("Independent Ground Proof", "Eliminates paper-only payment claims. Requires verified physical completion of riverbank boulder revetments, geo-bags, and spurs before disbursing central grants."),
            ("Zero 'Lost in Transit' Files", "Provides complete transparency over file custody, preventing inter-departmental delays across North-Eastern states.")
        ]),
        ("2. SCHEME MASTER & DPR BASELINE MANAGEMENT", [
            ("Codified National Register", "All 30 flood management and anti-erosion schemes codified with unique identifiers (e.g. SK-01, AS-12) permanently linking baseline estimates, bills, and site photos."),
            ("Locked 90:10 Funding Ratio", "System calculates and locks the 90% Central Share and 10% State Share automatically upon DPR approval, preventing arithmetic discrepancies during future installment claims."),
            ("Dynamic Geospatial Attributes", "Tracks each scheme's state, river basin, tributary, administrative district, and boundary geo-coordinates in one central master database.")
        ]),
        ("3. STATUTORY FORM GFR 12-A & FUND REQUISITIONS", [
            ("Statutory Rule 238(1) Compliance", "Mandatory General Financial Rules requirement to certify prior central grants were properly utilized before releasing next installments."),
            ("Automated 3-Click UC Generation", "Portal pre-populates opening unspent balances, interest accrued, and certified expenditure; generates official Government of India Form GFR 12-A PDF with zero math errors."),
            ("Three-Tier Approval Pipeline", "State WRD submits claim → Brahmaputra Board technical review & photo verification → Ministry of Jal Shakti (MoJS) final release via PFMS.")
        ])
    ]

    first_c1 = True
    for sec_title, items in col1_sections:
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
            p_i.space_before = Pt(3)

            run = p_i.add_run()
            run.text = item_b
            run.font.bold = False
            run.font.color.rgb = C_MUTED

    # --- RIGHT COLUMN ---
    col2_x = Inches(6.81)
    tx_c2 = slide.shapes.add_textbox(col2_x, y_body, w_col, h_body)
    tf_c2 = tx_c2.text_frame
    tf_c2.word_wrap = True
    tf_c2.margin_left = tf_c2.margin_top = tf_c2.margin_right = tf_c2.margin_bottom = 0

    col2_sections = [
        ("4. GEOTAGGED FIELD INSPECTIONS & AUDIT PROOF", [
            ("Mandatory Camera GPS Geotagging", "Field engineers capture on-site mobile photos embedded with real latitude, longitude, elevation, and timestamp. Prevents upload of stock, old, or duplicate photos."),
            ("Physical % vs Claimed Money Cross-Check", "System validates that physical progress matches or exceeds requested funds. Blocks recommendation if financial claims outpace verified field execution."),
            ("Pre-Monsoon Flood Readiness", "Audits flood spurs, boulder revetments, and emergency breach closures in March-April before high monsoon waters arrive.")
        ]),
        ("5. INTERACTIVE RIVER BASIN GIS SPATIAL TRACKING", [
            ("Comprehensive River Basin Visualisation", "Real-time spatial Leaflet engine mapping all 30 schemes across Teesta, Barak (Howrah, Tlawng, Jiri), Dhansiri, Lohit, Simsang, and Siang basins."),
            ("Multi-Layer Satellite & Status Filters", "Toggle between river basin topography and satellite views. Status-coded pins: Completed (17), Ongoing (5), Initial Phase (8) with 1-click fly-to navigation.")
        ]),
        ("6. EXECUTIVE MIS COCKPIT & FILE PENDENCY RADAR", [
            ("Desk-Level Custody & Aging Clock", "Identifies exactly whose desk a claim is sitting on (State WRD, Board Guwahati, or MoJS Delhi) and shows days elapsed (e.g. 'Pending for 6 days')."),
            ("Red-Flag Delays & 1-Click Excel Dossiers", "Automatically highlights files overdue past 15 or 30 days for leadership follow-up. Generates instant Excel and PDF briefing sheets for Parliamentary and Ministry reviews.")
        ]),
        ("7. DEPLOYMENT ARCHITECTURE & ROLE SECURITY", [
            ("Live Public Cloud Deployment", "Hosted on enterprise Ubuntu 24.04 VM (8 vCPU, 16 GB RAM) with 24/7 Linux systemd auto-recovery watchdog; accessible directly at https://103.162.247.169."),
            ("Role-Based Governance (Parichay Standard)", "Strict role segregation: Super Admin (approvals), Board Officials (inspection & recommendations), State WRDs (submission only), and MoJS (national sanction).")
        ])
    ]

    first_c2 = True
    for sec_title, items in col2_sections:
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
            p_i.space_before = Pt(3)

            run = p_i.add_run()
            run.text = item_b
            run.font.bold = False
            run.font.color.rgb = C_MUTED

    # Thin Divider Line 3
    line3 = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, Inches(0.8), Inches(6.88), Inches(11.73), Inches(0.015))
    line3.fill.solid()
    line3.fill.fore_color.rgb = C_LINE
    line3.line.fill.background()

    # 5. Clean Footer Text (No Box)
    tx_foot = slide.shapes.add_textbox(Inches(0.8), Inches(6.94), Inches(11.73), Inches(0.35))
    tf_foot = tx_foot.text_frame
    tf_foot.word_wrap = True
    tf_foot.margin_left = tf_foot.margin_top = tf_foot.margin_right = tf_foot.margin_bottom = 0

    p_foot = tf_foot.paragraphs[0]
    p_foot.text = "PRODUCTION URL:  https://103.162.247.169/dashboard   |   24/7 AUTO-RECOVERY WATCHDOG   |   ALL 30 SCHEMES & BASELINES FULLY PRELOADED"
    p_foot.font.size = Pt(9)
    p_foot.font.bold = True
    p_foot.font.color.rgb = C_BLUE
    p_foot.alignment = PP_ALIGN.CENTER

    prs.save(output_file)
    print(f"Pure text single-page presentation created at: {output_file}")

if __name__ == "__main__":
    out_files = [
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Single_Page_Summary.pptx",
        r"d:\fmbap-portal-main - Copy\FMBAP_Portal_Executive_OnePager.pptx"
    ]
    for f in out_files:
        build_pure_text_onepager(f)
