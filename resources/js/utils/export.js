import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';

// 导入 jspdf-autotable 插件
// 注意：side-effect import 会自动扩展 jsPDF.prototype
import 'jspdf-autotable';

/**
 * 手动绘制表格（当 autoTable 不可用时的后备方案）
 */
function drawTableManually(doc, headers, rows, startY) {
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 14;
    const tableWidth = pageWidth - (margin * 2);
    const colCount = headers.length;
    const colWidth = tableWidth / colCount;
    const rowHeight = 7;
    const headerHeight = 8;
    
    let currentY = startY;
    
    // 绘制表头
    doc.setFillColor(66, 139, 202);
    doc.rect(margin, currentY, tableWidth, headerHeight, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(9);
    doc.setFont(undefined, 'bold');
    
    headers.forEach((header, index) => {
        const x = margin + (index * colWidth);
        doc.text(header, x + 2, currentY + 5, { align: 'left' });
    });
    
    currentY += headerHeight;
    
    // 绘制数据行
    doc.setTextColor(0, 0, 0);
    doc.setFont(undefined, 'normal');
    
    rows.forEach((row, rowIndex) => {
        // 检查是否需要新页面
        if (currentY + rowHeight > pageHeight - 20) {
            doc.addPage();
            currentY = margin;
        }
        
        // 交替行背景色
        if (rowIndex % 2 === 0) {
            doc.setFillColor(245, 245, 245);
            doc.rect(margin, currentY, tableWidth, rowHeight, 'F');
        }
        
        // 绘制单元格边框和数据
        row.forEach((cell, colIndex) => {
            const x = margin + (colIndex * colWidth);
            doc.rect(x, currentY, colWidth, rowHeight, 'S');
            doc.text(String(cell || ''), x + 2, currentY + 4, { 
                align: 'left',
                maxWidth: colWidth - 4
            });
        });
        
        currentY += rowHeight;
    });
}

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
    try {
        // 验证参数
        if (!Array.isArray(data) || !Array.isArray(columns) || !filename || !title) {
            throw new Error('参数不完整：需要数据数组、列配置、文件名和标题');
        }

        if (data.length === 0) {
            throw new Error('没有数据可导出');
        }

        if (columns.length === 0) {
            throw new Error('没有列配置');
        }

        const doc = new jsPDF();

        // 设置标题
        doc.setFontSize(16);
        doc.text(title, 14, 15);

        let yPos = 25;

        // 如果有统计数据，显示统计信息
        if (stats && typeof stats === 'object') {
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
                let displayValue = String(value);
                if (typeof value == 'number' && !isNaN(value)) {
                    displayValue = value.toFixed(2);
                }
                doc.text(`${key}: ${displayValue}`, currentX, currentY);
                currentX += colWidth;
            });

            yPos = currentY + 10;
        }

        // 准备表头
        const headers = columns.map(col => col.label);

        // 准备数据行
        const rows = data.map((row, rowIndex) => {
            return columns.map(col => {
                try {
                    const value = col.key.split('.').reduce((obj, key) => obj?.[key], row);
                    if (value == null) return '';
                    if (typeof value == 'number' && !isNaN(value)) {
                        return value.toString();
                    }
                    return String(value);
                } catch (error) {
                    console.warn(`处理第 ${rowIndex + 1} 行数据时出错:`, error);
                    return '';
                }
            });
        });

        // 添加表格
        if (typeof doc.autoTable === 'function') {
            // 使用 autoTable 插件
            doc.autoTable({
                head: [headers],
                body: rows,
                startY: yPos,
                styles: { fontSize: 9 },
                headStyles: { fillColor: [66, 139, 202] },
                alternateRowStyles: { fillColor: [245, 245, 245] },
                margin: { top: 10 },
                theme: 'grid'
            });
        } else {
            // 后备方案：手动绘制表格
            drawTableManually(doc, headers, rows, yPos);
        }

        // 保存文件
        doc.save(`${filename}.pdf`);

    } catch (error) {
        console.error('PDF导出失败:', error);
        throw new Error(`PDF导出失败: ${error.message}`);
    }
}

