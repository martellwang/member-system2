const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  HeadingLevel, AlignmentType, LevelFormat, BorderStyle, WidthType,
  ShadingType, VerticalAlign
} = require('docx');
const fs = require('fs');

const border = { style: BorderStyle.SINGLE, size: 1, color: "CCCCCC" };
const borders = { top: border, bottom: border, left: border, right: border };
const cellMargins = { top: 80, bottom: 80, left: 120, right: 120 };

function h1(text) {
  return new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun({ text, bold: true })] });
}
function h2(text) {
  return new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun({ text, bold: true })] });
}
function p(text, opts = {}) {
  return new Paragraph({ children: [new TextRun({ text, ...opts })] });
}
function bullet(text) {
  return new Paragraph({
    numbering: { reference: "bullets", level: 0 },
    children: [new TextRun(text)]
  });
}
function spacer() {
  return new Paragraph({ children: [new TextRun("")] });
}

function headerCell(text) {
  return new TableCell({
    borders,
    width: { size: 3120, type: WidthType.DXA },
    shading: { fill: "534AB7", type: ShadingType.CLEAR },
    margins: cellMargins,
    verticalAlign: VerticalAlign.CENTER,
    children: [new Paragraph({ children: [new TextRun({ text, bold: true, color: "FFFFFF", size: 22 })] })]
  });
}

function dataCell(text, shade) {
  return new TableCell({
    borders,
    width: { size: 3120, type: WidthType.DXA },
    shading: { fill: shade || "FFFFFF", type: ShadingType.CLEAR },
    margins: cellMargins,
    children: [new Paragraph({ children: [new TextRun({ text, size: 22 })] })]
  });
}

function reqTable(rows) {
  return new Table({
    width: { size: 9360, type: WidthType.DXA },
    columnWidths: [1800, 3780, 3780],
    rows: [
      new TableRow({
        children: [
          new TableCell({ borders, width: { size: 1800, type: WidthType.DXA }, shading: { fill: "534AB7", type: ShadingType.CLEAR }, margins: cellMargins, children: [new Paragraph({ children: [new TextRun({ text: "編號", bold: true, color: "FFFFFF", size: 22 })] })] }),
          new TableCell({ borders, width: { size: 3780, type: WidthType.DXA }, shading: { fill: "534AB7", type: ShadingType.CLEAR }, margins: cellMargins, children: [new Paragraph({ children: [new TextRun({ text: "需求說明", bold: true, color: "FFFFFF", size: 22 })] })] }),
          new TableCell({ borders, width: { size: 3780, type: WidthType.DXA }, shading: { fill: "534AB7", type: ShadingType.CLEAR }, margins: cellMargins, children: [new Paragraph({ children: [new TextRun({ text: "詳細規格", bold: true, color: "FFFFFF", size: 22 })] })] }),
        ]
      }),
      ...rows.map((r, i) => new TableRow({
        children: [
          new TableCell({ borders, width: { size: 1800, type: WidthType.DXA }, shading: { fill: i % 2 === 0 ? "F5F3FF" : "FFFFFF", type: ShadingType.CLEAR }, margins: cellMargins, children: [new Paragraph({ children: [new TextRun({ text: r[0], bold: true, size: 22 })] })] }),
          new TableCell({ borders, width: { size: 3780, type: WidthType.DXA }, shading: { fill: i % 2 === 0 ? "F5F3FF" : "FFFFFF", type: ShadingType.CLEAR }, margins: cellMargins, children: [new Paragraph({ children: [new TextRun({ text: r[1], size: 22 })] })] }),
          new TableCell({ borders, width: { size: 3780, type: WidthType.DXA }, shading: { fill: i % 2 === 0 ? "F5F3FF" : "FFFFFF", type: ShadingType.CLEAR }, margins: cellMargins, children: r[2].map(d => new Paragraph({ numbering: { reference: "bullets", level: 0 }, children: [new TextRun({ text: d, size: 22 })] })) }),
        ]
      }))
    ]
  });
}

function fieldTable(title, fields) {
  return new Table({
    width: { size: 9360, type: WidthType.DXA },
    columnWidths: [3120, 3120, 3120],
    rows: [
      new TableRow({
        children: [
          new TableCell({ borders, width: { size: 3120, type: WidthType.DXA }, shading: { fill: "085041", type: ShadingType.CLEAR }, margins: cellMargins, columnSpan: 3, children: [new Paragraph({ children: [new TextRun({ text: title, bold: true, color: "FFFFFF", size: 22 })] })] }),
        ]
      }),
      new TableRow({
        children: [
          new TableCell({ borders, width: { size: 3120, type: WidthType.DXA }, shading: { fill: "E1F5EE", type: ShadingType.CLEAR }, margins: cellMargins, children: [new Paragraph({ children: [new TextRun({ text: "欄位名稱", bold: true, size: 22 })] })] }),
          new TableCell({ borders, width: { size: 3120, type: WidthType.DXA }, shading: { fill: "E1F5EE", type: ShadingType.CLEAR }, margins: cellMargins, children: [new Paragraph({ children: [new TextRun({ text: "資料類型", bold: true, size: 22 })] })] }),
          new TableCell({ borders, width: { size: 3120, type: WidthType.DXA }, shading: { fill: "E1F5EE", type: ShadingType.CLEAR }, margins: cellMargins, children: [new Paragraph({ children: [new TextRun({ text: "說明", bold: true, size: 22 })] })] }),
        ]
      }),
      ...fields.map((f, i) => new TableRow({
        children: [
          new TableCell({ borders, width: { size: 3120, type: WidthType.DXA }, shading: { fill: i % 2 === 0 ? "F5FFFA" : "FFFFFF", type: ShadingType.CLEAR }, margins: cellMargins, children: [new Paragraph({ children: [new TextRun({ text: f[0], size: 22 })] })] }),
          new TableCell({ borders, width: { size: 3120, type: WidthType.DXA }, shading: { fill: i % 2 === 0 ? "F5FFFA" : "FFFFFF", type: ShadingType.CLEAR }, margins: cellMargins, children: [new Paragraph({ children: [new TextRun({ text: f[1], size: 22, color: "0F6E56" })] })] }),
          new TableCell({ borders, width: { size: 3120, type: WidthType.DXA }, shading: { fill: i % 2 === 0 ? "F5FFFA" : "FFFFFF", type: ShadingType.CLEAR }, margins: cellMargins, children: [new Paragraph({ children: [new TextRun({ text: f[2], size: 22 })] })] }),
        ]
      }))
    ]
  });
}

const doc = new Document({
  numbering: {
    config: [
      {
        reference: "bullets",
        levels: [{ level: 0, format: LevelFormat.BULLET, text: "\u2022", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } }]
      }
    ]
  },
  styles: {
    default: { document: { run: { font: "Arial", size: 24 } } },
    paragraphStyles: [
      { id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 36, bold: true, font: "Arial", color: "534AB7" },
        paragraph: { spacing: { before: 360, after: 180 }, outlineLevel: 0,
          border: { bottom: { style: BorderStyle.SINGLE, size: 4, color: "534AB7", space: 4 } } } },
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 28, bold: true, font: "Arial", color: "3C3489" },
        paragraph: { spacing: { before: 240, after: 120 }, outlineLevel: 1 } },
    ]
  },
  sections: [{
    properties: {
      page: {
        size: { width: 11906, height: 16838 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 }
      }
    },
    children: [
      // 封面標題
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 1440, after: 240 },
        children: [new TextRun({ text: "會員管理系統", bold: true, size: 56, color: "534AB7", font: "Arial" })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { after: 120 },
        children: [new TextRun({ text: "設計規格文件", bold: true, size: 40, color: "3C3489", font: "Arial" })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { after: 720 },
        children: [new TextRun({ text: `版本 1.0　|　建立日期：${new Date().toLocaleDateString('zh-TW')}`, size: 22, color: "888780" })]
      }),

      spacer(),

      // 1. 系統概述
      h1("1. 系統概述"),
      p("本系統為會員管理平台，支援個人用戶與商業公司兩種會員類型，提供線上自助註冊功能及後台管理介面。"),
      spacer(),

      // 2. 功能需求
      h1("2. 功能需求"),
      spacer(),
      reqTable([
        ["REQ-001", "會員類型分類\n客戶分為個人用戶與商業公司兩種類型", ["個人用戶：記錄身分證號", "商業公司：記錄統一編號、公司名稱、網站網址"]],
        ["REQ-002", "線上自助會員註冊\n提供會員可自行線上進行會員註冊", ["個人/公司欄位分開設計", "必填欄位驗證", "密碼安全設定", "註冊成功 Email 驗證通知"]],
        ["REQ-003", "後台管理系統\n管理員使用的後台管理介面", ["統計儀表板（總人數、類型比例、待審核數）", "會員列表顯示（類型徽章、身份資料、狀態）", "篩選：全部 / 個人 / 公司 / 待審核", "即時搜尋（姓名、Email、證號、公司）", "會員狀態管理：啟用 / 待審核"]],
      ]),
      spacer(),

      // 3. 資料欄位定義
      h1("3. 資料欄位定義"),
      spacer(),
      h2("3.1 個人用戶欄位"),
      spacer(),
      fieldTable("個人用戶（Personal User）", [
        ["name", "String", "姓名（必填）"],
        ["email", "String / Email", "電子郵件（必填、唯一）"],
        ["phone", "String", "電話號碼"],
        ["password", "String (hashed)", "密碼（必填，至少8位）"],
        ["id_number", "String", "身分證號（必填，10碼）"],
        ["birth_date", "Date", "出生日期"],
        ["gender", "Enum", "性別（男 / 女 / 不公開）"],
        ["status", "Enum", "狀態（active / pending）"],
        ["created_at", "DateTime", "建立時間"],
      ]),
      spacer(),
      h2("3.2 商業公司用戶欄位"),
      spacer(),
      fieldTable("商業公司用戶（Company User）", [
        ["name", "String", "聯絡人姓名（必填）"],
        ["email", "String / Email", "電子郵件（必填、唯一）"],
        ["phone", "String", "電話號碼"],
        ["password", "String (hashed)", "密碼（必填，至少8位）"],
        ["tax_id", "String", "統一編號（必填，8碼）"],
        ["company_name", "String", "公司名稱（必填）"],
        ["website", "String / URL", "公司網站網址"],
        ["industry", "Enum", "產業類別"],
        ["status", "Enum", "狀態（active / pending）"],
        ["created_at", "DateTime", "建立時間"],
      ]),
      spacer(),

      // 4. 後台功能清單
      h1("4. 後台管理功能清單"),
      spacer(),
      p("後台管理系統包含以下功能：", { bold: true }),
      spacer(),
      bullet("統計儀表板：總會員數、個人用戶數、公司用戶數、待審核數量"),
      bullet("會員列表：分頁顯示，每頁可設定筆數"),
      bullet("類型篩選：全部 / 個人用戶 / 商業公司 / 待審核"),
      bullet("即時搜尋：依姓名、Email、身分證號、統編、公司名稱搜尋"),
      bullet("會員詳情：點選查看完整資料"),
      bullet("狀態管理：啟用 / 停用 / 審核通過"),
      bullet("匯出功能：匯出會員清單為 CSV / Excel"),
      spacer(),

      // 5. 備註
      h1("5. 備註"),
      p("本文件為初版規格，實際開發時可依需求調整。如需新增功能（如付費方案、權限管理、Email 驗證流程），請更新本文件並調整版本號。"),
      spacer(),
      new Paragraph({
        border: { top: { style: BorderStyle.SINGLE, size: 2, color: "CCCCCC", space: 8 } },
        spacing: { before: 480 },
        children: [new TextRun({ text: `文件版本 1.0　|　最後更新：${new Date().toLocaleDateString('zh-TW')}`, size: 18, color: "888780" })]
      }),
    ]
  }]
});

Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync('/mnt/user-data/outputs/會員管理系統設計規格.docx', buffer);
  console.log('Done');
});
