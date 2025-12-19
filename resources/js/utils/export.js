import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import 'jspdf-autotable';

/**
 * 导出Excel文件
 * @param {Array} data - 数据数组
 * @param {Array} columns - 列配置 [{key: '字段名', label: '显示名称'}]
 * @param {String} filename - 文件名
 */
export function exportToExcel(data, columns, filename) {
    // 准备表头
    const headers = columns.map(col => col.label);
    
    // 准备数据行
    const rows = data.map(row => {
        return columns.map(col => {
            const value = col.key.split('.').reduce((obj, key) => obj?.[key], row);
            return value != null ? value : '';
        });
    });
    
    // 创建工作簿
    const wb = XLSX.utils.book_new();
    
    // 创建工作表数据
    const wsData = [headers, ...rows];
    const ws = XLSX.utils.aoa_to_sheet(wsData);
    
    // 设置列宽
    const colWidths = columns.map(() => ({ wch: 15 }));
    ws['!cols'] = colWidths;
    
    // 添加工作表到工作簿
    XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
    
    // 导出文件
    XLSX.writeFile(wb, `${filename}.xlsx`);
}

/**
 * 导出PDF文件
 * @param {Array} data - 数据数组
 * @param {Array} columns - 列配置 [{key: '字段名', label: '显示名称'}]
 * @param {String} filename - 文件名
 * @param {String} title - 报表标题
 * @param {Object} stats - 统计数据（可选）
 */
export function exportToPDF(data, columns, filename, title, stats = null) {
    const doc = new jsPDF();
    
    // 设置标题
    doc.setFontSize(16);
    doc.text(title, 14, 15);
    
    let yPos = 25;
    
    // 如果有统计数据，显示统计信息
    if (stats) {
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text('统计信息', 14, yPos);
        yPos += 8;
        
        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        const statsEntries = Object.entries(stats);
        const colWidth = 90;
        const startX = 14;
        let currentX = startX;
        let currentY = yPos;
        
        statsEntries.forEach(([key, value], index) => {
            if (index > 0 && index % 2 == 0) {
                currentX = startX;
                currentY += 6;
            }
            const displayValue = typeof value == 'number' ? value.toFixed(2) : String(value);
            doc.text(`${key}: ${displayValue}`, currentX, currentY);
            currentX += colWidth;
        });
        
        yPos = currentY + 10;
    }
    
    // 准备表头
    const headers = columns.map(col => col.label);
    
    // 准备数据行
    const rows = data.map(row => {
        return columns.map(col => {
            const value = col.key.split('.').reduce((obj, key) => obj?.[key], row);
            return value != null ? String(value) : '';
        });
    });
    
    // 添加表格
    doc.autoTable({
        head: [headers],
        body: rows,
        startY: yPos,
        styles: { fontSize: 9 },
        headStyles: { fillColor: [66, 139, 202] },
        alternateRowStyles: { fillColor: [245, 245, 245] }
    });
    
    // 保存文件
    doc.save(`${filename}.pdf`);
}

