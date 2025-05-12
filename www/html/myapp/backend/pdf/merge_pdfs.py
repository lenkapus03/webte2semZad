#!/usr/bin/env python3
"""
PDF Merger - Simple tool to merge two PDF files into one.
Usage: python merge_pdfs.py input1.pdf input2.pdf output.pdf
"""

import sys
import os
from pypdf import PdfMerger

def merge_pdfs(input_files, output_file):
    """Merge multiple PDF files into one output file."""
    
    # Validate input files
    for file_path in input_files:
        if not os.path.exists(file_path):
            print(f"Error: File '{file_path}' does not exist.")
            return False
        
        if not file_path.lower().endswith('.pdf'):
            print(f"Error: File '{file_path}' is not a PDF file.")
            return False
    
    try:
        # Create a PDF merger object
        merger = PdfMerger()
        
        # Add each file to the merger
        for file_path in input_files:
            print(f"Adding: {file_path}")
            merger.append(file_path)
        
        # Write to output file
        print(f"Creating: {output_file}")
        merger.write(output_file)
        merger.close()
        
        print(f"Success! Merged {len(input_files)} files into '{output_file}'")
        return True
    
    except Exception as e:
        print(f"Error merging PDFs: {e}")
        return False

def main():
    # Check command line arguments
    if len(sys.argv) < 4:
        print("Usage: python merge_pdfs.py input1.pdf input2.pdf output.pdf")
        print("       You can specify more than two input files if needed.")
        return
    
    # Get input and output file paths
    input_files = sys.argv[1:-1]
    output_file = sys.argv[-1]
    
    if len(input_files) < 2:
        print("Error: Please specify at least two input PDF files to merge.")
        return
    
    # Merge the PDFs
    merge_pdfs(input_files, output_file)

if __name__ == "__main__":
    main()